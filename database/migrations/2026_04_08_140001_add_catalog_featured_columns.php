<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table): void {
            $table->boolean('is_featured')->default(false)->after('is_published');
            $table->unsignedInteger('catalog_view_count')->default(0)->after('is_featured');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->boolean('is_featured')->default(false)->after('is_published');
            $table->unsignedInteger('catalog_view_count')->default(0)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table): void {
            $table->dropColumn(['is_featured', 'catalog_view_count']);
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn(['is_featured', 'catalog_view_count']);
        });
    }
};
