<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->timestamp('last_login_at')->nullable();
        });

        // Every code sent is kept: it is the login / verification history for each mobile number.
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code_hash', 64);
            $table->string('driver', 20);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            // dateTime, not timestamp: MySQL auto-updates a table's first non-null TIMESTAMP on every UPDATE.
            $table->dateTime('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_verified_at', 'last_login_at']);
        });
    }
};
