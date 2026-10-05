<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

// The "5 verification emails per IP address per hour" limit.
// Shared by the Resend form and by the login page.
class EmailResendService
{
    public const MAX = 5;
    private const DECAY_SECONDS = 3600;

    public function exhausted(Request $request): bool
    {
        return RateLimiter::tooManyAttempts($this->key($request), self::MAX);
    }

    public function minutesLeft(Request $request): int
    {
        return max(1, (int) ceil(RateLimiter::availableIn($this->key($request)) / 60));
    }

    // Uses up one resend and returns how many are left.
    public function consume(Request $request): int
    {
        RateLimiter::hit($this->key($request), self::DECAY_SECONDS);

        return RateLimiter::remaining($this->key($request), self::MAX);
    }

    private function key(Request $request): string
    {
        return 'email-resend:' . $request->ip();
    }
}