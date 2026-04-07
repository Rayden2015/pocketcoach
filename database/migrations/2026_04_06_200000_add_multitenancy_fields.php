<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_super_admin')->default(false)->after('remember_token');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('status', 32)->default('active')->after('slug');
            $table->string('custom_domain')->nullable()->after('branding');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_super_admin');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['status', 'custom_domain']);
        });
    }
};
