<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar_url')->nullable()->after('phone');
            $table->string('timezone', 64)->default('Africa/Accra')->after('avatar_url');
            $table->string('locale', 16)->default('en')->after('timezone');
            $table->json('goals')->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'avatar_url', 'timezone', 'locale', 'goals']);
        });
    }
};
