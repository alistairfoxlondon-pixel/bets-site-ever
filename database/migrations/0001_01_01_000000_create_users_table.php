<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Access control
            $table->string('role', 20)->default('user')->index();
            $table->string('status', 20)->default('active')->index();

            // Profile
            $table->string('phone', 40)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('avatar_path', 255)->nullable();
            $table->string('locale', 5)->default('en');
            $table->string('currency', 3)->default('USD');
            $table->boolean('email_notifications')->default(true);

            // Two factor authentication (secret is encrypted at rest)
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->boolean('two_factor_enabled')->default(false);

            // Session bookkeeping
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->unsignedInteger('login_count')->default(0);

            // Wallet and referral programme
            $table->decimal('credits_balance', 12, 2)->default(0);
            $table->decimal('wallet_balance', 12, 2)->default(0);
            $table->string('referral_code', 24)->unique();
            $table->foreignId('referred_by')->nullable()->index();
            $table->timestamp('referred_at')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
