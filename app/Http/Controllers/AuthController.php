<?php

namespace App\Http\Controllers;

use App\Events\AccountLocked;
use App\Events\UserRegistered;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Events\OtpRequested;
use App\Services\OtpService;
use App\Events\EmailVerificationRequested;
use App\Services\EmailResendService;

class AuthController extends Controller
{
    private const MAX_FAILED_ATTEMPTS = 3;
    private const COOLING_MINUTES = 2;

    // ---------- Registration (unchanged from Phase 1) ----------

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $country = collect(config('countries'))->firstWhere('name', $data['country']);

        $user = DB::transaction(function () use ($data, $country) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_initial' => $data['middle_initial'] ?? null,
                'birthday' => Carbon::createFromFormat('!m/d/Y', $data['birthday'])->format('Y-m-d'),
                'password_hash' => Hash::make($data['password']),
                'email' => $data['email'],
                'mobile_number' => $country['dial'] . $data['mobile_number'],
            ]);

            $user->address()->create([
                'house_street' => $data['house_street'],
                'country' => $data['country'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip_code' => $data['zip_code'],
            ]);

            return $user;
        });

        event(new UserRegistered($user));

        return redirect()->route('check-email')->with('email', $user->email);
    }

    // ---------- Login ----------

    public function showLogin(): View
    {
        return view('auth.login');
    }

        public function login(Request $request, OtpService $otp, EmailResendService $resends): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Order from the plan: not locked -> email exists -> email verified -> password.
        // Every one of these failures looks the same and does the same amount of hashing work.
        if (! $user || $user->is_locked) {
            Hash::make($credentials['password']);

            return $this->loginFailed($request);
        }

        // Email not verified yet. The password is checked first, so the "not verified" page is only
        // shown to someone who knows the right password. A wrong password gets the generic error.
        if ($user->email_verified_at === null) {
            if (! Hash::check($credentials['password'], $user->password_hash)) {
                $this->recordFailure($user);

                return $this->loginFailed($request);
            }

            return $this->sendToEmailVerification($user, $request, $resends);
        }

        if (! Hash::check($credentials['password'], $user->password_hash)) {
            $this->recordFailure($user);

            return $this->loginFailed($request);
        }

        // Success: reset the counter and start a fresh session.
        if ($user->failed_login_attempts > 0) {
            $user->update(['failed_login_attempts' => 0]);
        }

        $request->session()->regenerate();

        // Mobile not verified yet: the password was right, but the login only finishes after
        // the code is entered, so user_id is NOT set yet (typing /landing will not work).
        if (! $user->mobile_verified) {
            $request->session()->put('otp_user_id', $user->id);
            $request->session()->put('otp_login_ok', true);

            if (! $otp->active($user)) {
                event(new OtpRequested($user));
            }

            return redirect()->route('verify-mobile');
        }

        $request->session()->put('user_id', $user->id);

        return redirect()->route('landing');
    }

    private function loginFailed(Request $request): RedirectResponse
    {
        return redirect()->route('login')
            ->withInput($request->only('email'))
            ->withErrors(['login' => 'Invalid email or password']);
    }

    // Right password but email not verified: send a new link (within the 5 per hour limit)
    // and open the "Check your email" page.
    private function sendToEmailVerification(User $user, Request $request, EmailResendService $resends): RedirectResponse
    {
        if ($resends->exhausted($request)) {
            $minutes = $resends->minutesLeft($request);

            return redirect()->route('check-email')
                ->with('email', $user->email)
                ->withErrors(['email' => 'Your email is not verified yet, and you have used all ' . EmailResendService::MAX . ' resends for now. Please try again in ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . '.']);
        }

        $left = $resends->consume($request);

        event(new EmailVerificationRequested($user));

        return redirect()->route('check-email')
            ->with('email', $user->email)
            ->with('status', 'Your email is not verified yet. We sent a new verification link to your inbox. You have ' . $left . ' resend' . ($left === 1 ? '' : 's') . ' left this hour.');
    }

    // Only called for a wrong password on an existing, verified, unlocked account.
    private function recordFailure(User $user): void
    {
        $user->increment('failed_login_attempts');

        if ($user->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {
            $user->update([
                'is_locked' => true,
                'lockout_until' => now()->addMinutes(self::COOLING_MINUTES),
            ]);

            event(new AccountLocked($user));
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }

    // ---------- Unlock ----------

    public function unlock(Request $request, TokenService $tokens): View
    {
        $token = (string) $request->query('token', '');
        $record = $token !== '' ? $tokens->find($token, 'account_unlock') : null;

        if (! $record || $record->expired_at->isPast()) {
            return view('auth.unlock', ['status' => 'invalid']);
        }

        $user = $record->user;

        // 2-minute cooling period: reject WITHOUT consuming the token.
        if ($user->lockout_until && now()->lt($user->lockout_until)) {
            $country = $user->address?->country;
            $zone = collect(config('countries'))->firstWhere('name', $country)['timeZone'] ?? 'Asia/Manila';

            return view('auth.unlock', [
                'status' => 'wait',
                'until' => $user->lockout_until->copy()->setTimezone($zone)->format('h:i:s A'),
                'retryUrl' => $request->fullUrl(),
            ]);
        }

        $user->update([
            'failed_login_attempts' => 0,
            'is_locked' => false,
            'lockout_until' => null,
        ]);

        $record->delete();

        return view('auth.unlock', ['status' => 'success']);
    }
}