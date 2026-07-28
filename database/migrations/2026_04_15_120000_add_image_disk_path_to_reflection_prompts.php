<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('reflection_prompts', 'image_disk_path')) {
            return;
        }

        Schema::table('reflection_prompts', function (Blueprint $table): void {
            $table->string('image_disk_path')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('reflection_prompts', 'image_disk_path')) {
            return;
        }

        Schema::table('reflection_prompts', function (Blueprint $table): void {
            $table->dropColumn('image_disk_path');
        });
    }
};
