<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 500);
            $table->string('normalized_url', 500);
            $table->string('domain', 191)->index();
            $table->string('mode', 20)->default('standard');
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedTinyInteger('score')->nullable();
            $table->unsignedTinyInteger('probability')->nullable();
            $table->string('verdict', 40)->nullable();
            $table->string('model', 120)->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->decimal('cost', 12, 6)->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->unsignedInteger('pages_crawled')->default(0);
            $table->unsignedInteger('findings_count')->default(0);
            $table->text('error_message')->nullable();
            $table->string('ip', 45)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
