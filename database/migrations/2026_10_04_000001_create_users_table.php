<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('middle_initial', 2)->nullable();
            $table->date('birthday');
            $table->string('password_hash', 255);
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('mobile_number', 20);
            $table->boolean('mobile_verified')->default(false);
            $table->integer('failed_login_attempts')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('lockout_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};