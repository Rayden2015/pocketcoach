<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignKeyIfExists('courses', 'program_id');
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

        $this->dropForeignKeyIfExists('lessons', 'module_id');
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

    /**
     * Laravel assumes names like `courses_program_id_foreign`; MySQL may use a different name,
     * or the FK may be missing (imported schema / partial migration). Resolve from information_schema.
     */
    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $database = $connection->getDatabaseName();
            $row = DB::selectOne(
                'SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                 AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1',
                [$database, $table, $column]
            );

            if ($row !== null && isset($row->name) && $row->name !== '') {
                DB::statement('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$row->name.'`');
            }

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->dropForeign([$column]);
        });
    }
};
