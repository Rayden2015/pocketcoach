<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('lesson_type', 32)->default('text');
            $table->longText('body')->nullable();
            $table->string('media_url')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['module_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
