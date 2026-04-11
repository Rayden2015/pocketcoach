<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use App\Services\CourseCurriculumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCurriculumServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_flattened_published_lessons_loads_module_lessons_when_not_eager_loaded(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M',
            'slug' => 'm',
            'sort_order' => 72,
            'is_published' => true,
        ]);

        Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => null,
            'title' => 'Root',
            'slug' => 'root',
            'lesson_type' => 'text',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Nested',
            'slug' => 'nested',
            'lesson_type' => 'text',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $fresh = Course::query()->findOrFail($course->id);
        $slugs = CourseCurriculumService::flattenedPublishedLessons($fresh)->pluck('slug')->all();

        $this->assertSame(['root', 'nested'], $slugs);
    }

    public function test_flattened_published_lessons_queries_lessons_when_modules_loaded_without_lessons(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M',
            'slug' => 'm',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'L',
            'slug' => 'in-mod',
            'lesson_type' => 'text',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $course->load('modules');

        $slugs = CourseCurriculumService::flattenedPublishedLessons($course)->pluck('slug')->all();

        $this->assertSame(['in-mod'], $slugs);
    }

    public function test_published_lesson_ids_for_course_includes_root_and_module_lessons(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't']);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M',
            'slug' => 'm',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => null,
            'title' => 'Root',
            'slug' => 'root',
            'lesson_type' => 'text',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Mod',
            'slug' => 'mod',
            'lesson_type' => 'text',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $ids = CourseCurriculumService::publishedLessonIdsForCourse($course->id)->all();

        $this->assertCount(2, $ids);
    }
}
