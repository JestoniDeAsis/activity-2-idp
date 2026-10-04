<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    use HasUuids;

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_initial',
        'birthday',
        'password_hash',
        'email',
        'email_verified_at',
        'mobile_number',
        'mobile_verified',
        'failed_login_attempts',
        'is_locked',
        'lockout_until',
    ];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'email_verified_at' => 'datetime',
            'lockout_until' => 'datetime',
            'mobile_verified' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(VerificationToken::class);
    }
}