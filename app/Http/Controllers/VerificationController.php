<?php

namespace App\Http\Controllers;

use App\Events\EmailVerificationRequested;
use App\Events\OtpRequested;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Services\EmailResendService;
use App\Services\OtpService;

class VerificationController extends Controller
{
    public function checkEmail(): View
    {
        return view('auth.check-email');
    }

    public function resend(Request $request, EmailResendService $resends, OtpService $otp): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($request->input('email')));

        // Registered (or logged in with the right password) in this browser, and the email was
        // verified meanwhile (other tab or phone): go on to the mobile step. No email is sent
        // and no resend is used.
        $pending = $request->session()->has('pending_user_id')
            ? User::find($request->session()->get('pending_user_id'))
            : null;

        if ($pending && $pending->email === $email && $pending->email_verified_at !== null) {
            if ($pending->mobile_verified) {
                return redirect()->route('login')->with('status', 'Your account is already verified. You can now log in.');
            }

            $request->session()->regenerate();
            $request->session()->put('otp_user_id', $pending->id);
            $request->session()->forget('otp_login_ok');

            if (! $otp->active($pending)) {
                event(new OtpRequested($pending));
            }

            return redirect()->route('verify-mobile');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('check-email')
                ->with('email', $email)
                ->withErrors(['email' => 'There is no account with this email yet. Please register first.']);
        }

        if ($user->email_verified_at !== null) {
            return redirect()->route('check-email')
                ->with('email', $email)
                ->with('status', 'This email is already verified. You can log in.');
        }

        // Only a real send uses up one of the 5 resends per IP address per hour (shared with the login page).
        if ($resends->exhausted($request)) {
            $minutes = $resends->minutesLeft($request);

            return redirect()->route('check-email')
                ->with('email', $email)
                ->withErrors(['email' => 'You have used all ' . EmailResendService::MAX . ' resends for now. Please try again in ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . '.']);
        }

        $left = $resends->consume($request);

        event(new EmailVerificationRequested($user));

        return redirect()->route('check-email')
            ->with('email', $email)
            ->with('status', 'We sent a new verification link to your inbox. You have ' . $left . ' resend' . ($left === 1 ? '' : 's') . ' left this hour.');
    }

    public function verify(Request $request, TokenService $tokens): View|RedirectResponse
    {
        $token = (string) $request->query('token', '');
        $record = $token !== '' ? $tokens->find($token, 'email_verify') : null;

        if (! $record || $record->expired_at->isPast()) {
            return view('auth.email-verified', ['status' => 'invalid']);
        }

        $user = $record->user;
        $user->email_verified_at = now();
        $user->save();

        $record->delete();

        // Mobile already verified: nothing more to do.
        if ($user->mobile_verified) {
            return view('auth.email-verified', ['status' => 'success']);
        }

        // Next step: send the SMS code and open the code page.
        $request->session()->regenerate();
        $request->session()->put('otp_user_id', $user->id);
        $request->session()->forget('otp_login_ok');

        event(new OtpRequested($user));

        return redirect()->route('verify-mobile');
    }
}