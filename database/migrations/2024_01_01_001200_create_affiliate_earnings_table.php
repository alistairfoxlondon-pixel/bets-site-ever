<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 40)->index();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('rate', 6, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending')->index();
            $table->string('description', 191)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_earnings');
    }
};
