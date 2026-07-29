<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('space_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 32);
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('space_invites');
    }
};
