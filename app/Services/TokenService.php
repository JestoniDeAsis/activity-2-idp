<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationToken;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TokenService
{
    public function hash(string $token): string
    {
        return hash_hmac('sha256', $token, config('app.key'));
    }

    // Deletes old tokens of the same type for this user, stores a new hashed one,
    // and returns the plain token (only ever sent by email).
    public function issue(User $user, string $type, Carbon $expiresAt): string
    {
        VerificationToken::where('user_id', $user->id)->where('type', $type)->delete();

        $token = Str::random(64);

        VerificationToken::create([
            'user_id' => $user->id,
            'token_hash' => $this->hash($token),
            'type' => $type,
            'expired_at' => $expiresAt,
        ]);

        return $token;
    }

    public function find(string $token, string $type): ?VerificationToken
    {
        return VerificationToken::where('token_hash', $this->hash($token))
            ->where('type', $type)
            ->first();
    }
}