<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 255);
            $table->enum('type', ['email_verify', 'mobile_otp', 'password_reset', 'account_unlock']);
            $table->timestamp('expired_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_tokens');
    }
};