<?php

namespace App\Listeners;

use App\Events\EmailVerificationRequested;
use App\Events\UserRegistered;
use App\Mail\VerifyEmailMail;
use App\Services\TokenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

// Runs right away (no queue). Laravel finds it automatically from the type hint.
class SendVerificationEmail
{
    public function __construct(private TokenService $tokens)
    {
    }

    public function handle(UserRegistered|EmailVerificationRequested $event): void
    {
        $user = $event->user;

        try {
            $token = $this->tokens->issue($user, 'email_verify', now()->addHours(24));
            Mail::to($user->email)->send(new VerifyEmailMail($user, $token));
        } catch (Throwable $e) {
            // The account is already saved, so do not crash. The user can press "Resend".
            Log::error('Verification email failed: ' . $e->getMessage());
        }
    }
}