<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('headline')->nullable()->after('name');
            $table->text('bio')->nullable()->after('headline');
            $table->string('linkedin_url', 512)->nullable()->after('avatar_url');
            $table->string('website_url', 512)->nullable()->after('linkedin_url');
            $table->string('twitter_url', 512)->nullable()->after('website_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['headline', 'bio', 'linkedin_url', 'website_url', 'twitter_url']);
        });
    }
};
