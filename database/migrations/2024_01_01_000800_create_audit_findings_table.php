<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40)->index();
            $table->string('severity', 20)->default('info')->index();
            $table->string('title', 255);
            $table->text('detail')->nullable();
            $table->text('recommendation')->nullable();
            $table->json('evidence')->nullable();
            $table->unsignedInteger('weight')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['audit_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_findings');
    }
};
