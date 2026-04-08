<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropForeign(['program_id']);
        });
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropUnique(['program_id', 'slug']);
        });
        Schema::table('courses', function (Blueprint $table): void {
            $table->foreignId('program_id')->nullable()->change();
        });
        Schema::table('courses', function (Blueprint $table): void {
            $table->foreign('program_id')->references('id')->on('programs')->nullOnDelete();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::table('lessons', function (Blueprint $table): void {
            $table->foreignId('course_id')->nullable()->after('tenant_id')->constrained()->cascadeOnDelete();
        });

        foreach (DB::table('lessons')->select('id', 'module_id')->get() as $row) {
            $courseId = DB::table('modules')->where('id', $row->module_id)->value('course_id');
            if ($courseId !== null) {
                DB::table('lessons')->where('id', $row->id)->update(['course_id' => $courseId]);
            }
        }

        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropForeign(['module_id']);
        });
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropUnique(['module_id', 'slug']);
        });
        Schema::table('lessons', function (Blueprint $table): void {
            $table->foreignId('module_id')->nullable()->change();
        });
        Schema::table('lessons', function (Blueprint $table): void {
            $table->foreign('module_id')->references('id')->on('modules')->nullOnDelete();
        });

        Schema::table('lessons', function (Blueprint $table): void {
            $table->foreignId('course_id')->nullable(false)->change();
        });

        Schema::table('lessons', function (Blueprint $table): void {
            $table->unique(['course_id', 'slug']);
        });
    }

    public function down(): void
    {
    }
};
