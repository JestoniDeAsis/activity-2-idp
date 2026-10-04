<?php

namespace App\Http\Controllers;

use App\Events\OtpRequested;
use App\Models\User;
use App\Services\OtpService;
use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MobileController extends Controller
{
    public function show(Request $request, OtpService $otp): View|RedirectResponse
    {
        $user = $this->otpUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.verify-mobile', [
            'maskedMobile' => $this->mask($user->mobile_number),
            'hasActive' => (bool) $otp->active($user),
            'attemptsLeft' => $otp->attemptsLeft($user),
            'resendIn' => $otp->secondsUntilResend($user),
        ]);
    }

    public function verify(Request $request, OtpService $otp, TokenService $tokens): RedirectResponse
    {
        $user = $this->otpUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'regex:/^\d{6}$/'],
        ], [
            'code.required' => 'Enter the 6-digit code.',
            'code.regex' => 'Enter the 6-digit code.',
        ]);

        $record = $otp->active($user);

        if (! $record) {
            return $this->codeError('This code has expired. Press "Resend OTP" to get a new one.');
        }

        if (! hash_equals($record->token_hash, $tokens->hash($request->input('code')))) {
            $attempts = $otp->recordFailure($user);

            if ($attempts >= OtpService::MAX_ATTEMPTS) {
                $otp->invalidate($user);

                return $this->codeError('Too many wrong attempts. Press "Resend OTP" to get a new code.');
            }

            $left = OtpService::MAX_ATTEMPTS - $attempts;

            return $this->codeError('Incorrect code. You have ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.');
        }

        $user->update(['mobile_verified' => true]);
        $otp->invalidate($user);

        // Came from the login page (password already checked): finish the login now.
        $loggingIn = (bool) $request->session()->pull('otp_login_ok', false);
        $request->session()->forget('otp_user_id');

        if ($loggingIn) {
            $request->session()->regenerate();
            $request->session()->put('user_id', $user->id);

            return redirect()->route('landing');
        }

        // Came from the email link (no password yet): the user still has to log in.
        return redirect()->route('login')->with('status', 'Mobile number verified. You can now log in.');
    }

    public function resend(Request $request, OtpService $otp): RedirectResponse
    {
        $user = $this->otpUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $wait = $otp->secondsUntilResend($user);

        if ($wait > 0) {
            return $this->codeError('Please wait ' . $wait . ' second' . ($wait === 1 ? '' : 's') . ' before asking for a new code.');
        }

        event(new OtpRequested($user));

        return redirect()->route('verify-mobile')->with('status', 'A new code was sent to your mobile number.');
    }

    // Only users whose email is verified and whose mobile is not verified yet.
    private function otpUser(Request $request): ?User
    {
        $id = $request->session()->get('otp_user_id');
        $user = $id ? User::find($id) : null;

        return ($user && $user->email_verified_at && ! $user->mobile_verified) ? $user : null;
    }

    private function codeError(string $message): RedirectResponse
    {
        return redirect()->route('verify-mobile')->withErrors(['code' => $message]);
    }

    private function mask(string $number): string
    {
        $length = strlen($number);

        if ($length <= 6) {
            return $number;
        }

        return substr($number, 0, 4) . str_repeat('*', $length - 6) . substr($number, -2);
    }
}