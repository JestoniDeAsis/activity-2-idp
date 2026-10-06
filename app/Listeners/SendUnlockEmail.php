<?php

namespace App\Listeners;

use App\Events\AccountLocked;
use App\Mail\AccountLockedMail;
use App\Services\TokenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

// Runs right away (no queue). Laravel finds it automatically from the type hint.
class SendUnlockEmail
{
    public function __construct(private TokenService $tokens)
    {
    }

    public function handle(AccountLocked $event): void
    {
        $user = $event->user;

        try {
            // issue() deletes old account_unlock tokens for this user first.
            // $token = $this->tokens->issue($user, 'account_unlock', now()->addHour());
            $token = $this->tokens->issue($user, 'account_unlock', now()->addHours(24));
            Mail::to($user->email)->send(new AccountLockedMail($user, $token));
        } catch (Throwable $e) {
            Log::error('Unlock email failed: ' . $e->getMessage());
        }
    }
}