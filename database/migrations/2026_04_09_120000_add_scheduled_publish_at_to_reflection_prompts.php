<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: fresh installs get this column from 2026_04_08_140002; older DBs that ran the
        // original create migration without it still receive the column here.
        if (Schema::hasColumn('reflection_prompts', 'scheduled_publish_at')) {
            return;
        }

        Schema::table('reflection_prompts', function (Blueprint $table): void {
            $table->timestamp('scheduled_publish_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('reflection_prompts', 'scheduled_publish_at')) {
            return;
        }

        Schema::table('reflection_prompts', function (Blueprint $table): void {
            $table->dropColumn('scheduled_publish_at');
        });
    }
};
