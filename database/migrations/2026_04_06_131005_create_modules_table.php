<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['course_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
