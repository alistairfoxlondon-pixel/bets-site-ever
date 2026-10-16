<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('article')->index();
            $table->string('mode', 20)->default('standard');
            $table->string('title', 255);
            $table->string('slug', 255)->index();
            $table->string('keyword', 191);
            $table->string('intent', 40)->default('informational');
            $table->unsignedInteger('word_count')->default(0);
            $table->longText('body')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('meta_title', 191)->nullable();
            $table->text('meta_description')->nullable();
            $table->json('faq')->nullable();
            $table->json('outline')->nullable();
            $table->json('images')->nullable();
            $table->json('internal_links')->nullable();
            $table->json('external_links')->nullable();
            $table->string('model', 120)->nullable();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->decimal('cost', 12, 6)->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_documents');
    }
};
