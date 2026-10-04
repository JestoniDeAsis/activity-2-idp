<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationToken;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    public const MINUTES_VALID = 5;
    public const MAX_ATTEMPTS = 3;
    public const RESEND_SECONDS = 60;

    public function __construct(private TokenService $tokens, private SmsService $sms)
    {
    }

    // The current code, if it has not expired.
    public function active(User $user): ?VerificationToken
    {
        return VerificationToken::where('user_id', $user->id)
            ->where('type', 'mobile_otp')
            ->where('expired_at', '>', now())
            ->first();
    }

    // Makes a new code (replacing any old one), resets the attempts, and sends the SMS.
    public function issueAndSend(User $user): bool
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::MINUTES_VALID);

        VerificationToken::where('user_id', $user->id)->where('type', 'mobile_otp')->delete();

        VerificationToken::create([
            'user_id' => $user->id,
            'token_hash' => $this->tokens->hash($code),
            'type' => 'mobile_otp',
            'expired_at' => $expiresAt,
        ]);

        Cache::forget($this->attemptsKey($user));
        Cache::put($this->sentKey($user), now()->timestamp, $expiresAt);

        // The expiry time is shown in the time zone of the user's selected country.
        $zone = $this->zoneFor($user);
        $until = $expiresAt->copy()->setTimezone($zone)->format('h:i A');

        $message = config('app.name') . ' verification code: ' . $code
            . '. Valid for ' . self::MINUTES_VALID . ' minutes, until ' . $until . ' (' . $zone . ' time).'
            . ' Never share this code.';

        return $this->sms->send($user->mobile_number, $message);
    }

    // Counts a wrong entry and returns how many wrong entries there are now.
    public function recordFailure(User $user): int
    {
        $key = $this->attemptsKey($user);
        $ttl = now()->addMinutes(self::MINUTES_VALID);

        Cache::add($key, 0, $ttl);
        $count = Cache::increment($key);

        if ($count === false) {
            Cache::put($key, 1, $ttl);
            $count = 1;
        }

        return (int) $count;
    }

    public function attemptsLeft(User $user): int
    {
        return max(0, self::MAX_ATTEMPTS - (int) Cache::get($this->attemptsKey($user), 0));
    }

    // Deletes the current code and clears the attempt counter.
    // The "sent at" marker stays, so the 60-second resend wait still counts.
    public function invalidate(User $user): void
    {
        VerificationToken::where('user_id', $user->id)->where('type', 'mobile_otp')->delete();
        Cache::forget($this->attemptsKey($user));
    }

    public function secondsUntilResend(User $user): int
    {
        $sent = Cache::get($this->sentKey($user));

        if (! $sent) {
            return 0;
        }

        return max(0, self::RESEND_SECONDS - (now()->timestamp - (int) $sent));
    }

    private function zoneFor(User $user): string
    {
        $country = $user->address?->country;

        return collect(config('countries'))->firstWhere('name', $country)['timeZone'] ?? 'Asia/Manila';
    }

    private function attemptsKey(User $user): string
    {
        return 'otp_attempts:' . $user->id;
    }

    private function sentKey(User $user): string
    {
        return 'otp_sent:' . $user->id;
    }
}