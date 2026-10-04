<?php

namespace App\Listeners;

use App\Events\OtpRequested;
use App\Services\OtpService;
use Illuminate\Support\Facades\Log;
use Throwable;

// Runs right away (no queue). Laravel finds it automatically from the type hint.
class SendOtpSms
{
    public function __construct(private OtpService $otp)
    {
    }

    public function handle(OtpRequested $event): void
    {
        try {
            $this->otp->issueAndSend($event->user);
        } catch (Throwable $e) {
            // Never crash the page. The user can press "Resend OTP".
            Log::error('OTP SMS failed: ' . $e->getMessage());
        }
    }
}