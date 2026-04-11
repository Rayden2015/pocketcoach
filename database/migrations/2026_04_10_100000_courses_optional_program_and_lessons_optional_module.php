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

        // Backfill course_id from the lesson’s module (all lessons still have module_id here).
        $links = DB::table('lessons')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->whereNotNull('modules.course_id')
            ->select('lessons.id as lesson_id', 'modules.course_id')
            ->get();

        foreach ($links as $link) {
            DB::table('lessons')->where('id', $link->lesson_id)->update(['course_id' => $link->course_id]);
        }

        $stillNull = (int) DB::table('lessons')->whereNull('course_id')->count();
        if ($stillNull > 0) {
            throw new RuntimeException(
                "Cannot require lessons.course_id: {$stillNull} lesson row(s) have no course_id (missing module, or module.course_id is null). Fix data before re-running this migration."
            );
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

    public function down(): void {}
};
