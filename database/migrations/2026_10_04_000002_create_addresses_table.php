<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('house_street', 255);
            $table->string('country', 100);
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('zip_code', 20);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};