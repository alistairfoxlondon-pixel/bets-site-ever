<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('tagline', 160)->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->decimal('price_monthly_bdt', 10, 2)->default(0);
            $table->decimal('price_yearly_bdt', 10, 2)->default(0);
            $table->unsignedInteger('credits')->default(0);
            $table->unsignedInteger('max_sites')->nullable();
            $table->unsignedInteger('trial_days')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
