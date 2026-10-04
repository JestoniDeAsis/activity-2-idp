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

class VerificationController extends Controller
{
    public function checkEmail(): View
    {
        return view('auth.check-email');
    }

    public function resend(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($request->input('email')));
        $user = User::where('email', $email)->first();

        // Same message every time, so nobody can find out which emails are registered.
        if ($user && $user->email_verified_at === null) {
            event(new EmailVerificationRequested($user));
        }

        return redirect()->route('check-email')
            ->with('email', $email)
            ->with('status', 'If that email is registered and not verified yet, we sent a new verification link. Please check your inbox.');
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