<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reflection_prompts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('body');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_publish_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_published', 'published_at']);
        });

        Schema::create('reflection_prompt_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reflection_prompt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('first_viewed_at');
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['reflection_prompt_id', 'user_id']);
        });

        Schema::create('reflection_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reflection_prompt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('first_submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['reflection_prompt_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reflection_responses');
        Schema::dropIfExists('reflection_prompt_views');
        Schema::dropIfExists('reflection_prompts');
    }
};
