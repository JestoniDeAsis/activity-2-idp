<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

// The only file that knows about Textbee. Change it here if Textbee changes its API.
class SmsService
{
    public function send(string $to, string $message): bool
    {
        $key = (string) config('textbee.api_key');

        // Dev fallback: no key yet, so write the SMS to storage/logs/laravel.log.
        if ($key === '') {
            Log::warning("SMS NOT SENT (TEXTBEE_API_KEY is empty). To: {$to} | Message: {$message}");

            return true;
        }

        $payload = ['recipients' => [$to], 'message' => $message];

        $device = (string) config('textbee.device_id');
        if ($device !== '') {
            $payload['deviceId'] = $device;
        }

        try {
            $res = Http::timeout(15)
                ->withHeaders(['x-api-key' => $key])
                ->acceptJson()
                ->asJson()
                ->post(rtrim((string) config('textbee.base_url'), '/') . '/gateway/send-sms', $payload);
        } catch (Throwable $e) {
            Log::error('Textbee request failed: ' . $e->getMessage());

            return false;
        }

        if (! $res->successful()) {
            Log::error('Textbee SMS failed: HTTP ' . $res->status() . ' ' . $res->body());

            return false;
        }

        return true;
    }
}