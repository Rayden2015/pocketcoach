<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table): void {
            $table->boolean('notes_is_public')->default(false)->after('notes');
        });

        Schema::table('reflection_responses', function (Blueprint $table): void {
            $table->boolean('is_public')->default(false)->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table): void {
            $table->dropColumn('notes_is_public');
        });

        Schema::table('reflection_responses', function (Blueprint $table): void {
            $table->dropColumn('is_public');
        });
    }
};
