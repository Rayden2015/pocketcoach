<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnLessonProgressWebTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{tenant: Tenant, course: Course, lesson1: Lesson, lesson2: Lesson, user: User}
     */
    private function enrolledLearnerWithTwoLessons(): array
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
        $lesson1 = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'L1',
            'slug' => 'l1',
            'lesson_type' => 'text',
            'body' => 'One',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $lesson2 = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'L2',
            'slug' => 'l2',
            'lesson_type' => 'text',
            'body' => 'Two',
            'sort_order' => 2,
            'is_published' => true,
        ]);
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'course_id' => $course->id,
            'source' => 'test',
            'status' => 'active',
        ]);

        return compact('tenant', 'course', 'lesson1', 'lesson2', 'user');
    }

    public function test_save_notes_persists_without_completing(): void
    {
        $s = $this->enrolledLearnerWithTwoLessons();
        $this->actingAs($s['user']);

        $this->post(route('learn.lesson.progress', [$s['tenant'], $s['lesson1']]), [
            'notes' => 'My reflection',
            'intent' => 'save_notes',
        ])->assertRedirect(route('learn.lesson', [$s['tenant'], $s['lesson1']]));

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $s['user']->id,
            'lesson_id' => $s['lesson1']->id,
            'notes' => 'My reflection',
        ]);
        $row = LessonProgress::query()->where('lesson_id', $s['lesson1']->id)->where('user_id', $s['user']->id)->first();
        $this->assertNull($row->completed_at);
    }

    public function test_complete_marks_lesson_and_sets_progress_percent(): void
    {
        $s = $this->enrolledLearnerWithTwoLessons();
        $this->actingAs($s['user']);

        $this->post(route('learn.lesson.progress', [$s['tenant'], $s['lesson1']]), [
            'notes' => '',
            'intent' => 'complete',
            'content_progress_percent' => 40,
            'position_seconds' => 0,
        ])->assertRedirect(route('learn.lesson', [$s['tenant'], $s['lesson1']]));

        $row = LessonProgress::query()->where('lesson_id', $s['lesson1']->id)->where('user_id', $s['user']->id)->first();
        $this->assertNotNull($row->completed_at);
        $this->assertSame(100, (int) $row->content_progress_percent);
    }

    public function test_next_marks_complete_and_redirects_to_following_lesson(): void
    {
        $s = $this->enrolledLearnerWithTwoLessons();
        $this->actingAs($s['user']);

        $this->post(route('learn.lesson.progress', [$s['tenant'], $s['lesson1']]), [
            'notes' => 'Leaving note',
            'intent' => 'next',
            'content_progress_percent' => 90,
            'position_seconds' => 5,
        ])->assertRedirect(route('learn.lesson', [$s['tenant'], $s['lesson2']]));

        $row = LessonProgress::query()->where('lesson_id', $s['lesson1']->id)->where('user_id', $s['user']->id)->first();
        $this->assertNotNull($row->completed_at);
        $this->assertSame('Leaving note', $row->notes);
        $this->assertSame(100, (int) $row->content_progress_percent);
        $this->assertSame(5, (int) $row->position_seconds);
    }

    public function test_incomplete_clears_completed_at(): void
    {
        $s = $this->enrolledLearnerWithTwoLessons();
        $this->actingAs($s['user']);

        LessonProgress::query()->create([
            'tenant_id' => $s['tenant']->id,
            'user_id' => $s['user']->id,
            'lesson_id' => $s['lesson1']->id,
            'completed_at' => now(),
            'content_progress_percent' => 100,
        ]);

        $this->post(route('learn.lesson.progress', [$s['tenant'], $s['lesson1']]), [
            'notes' => '',
            'intent' => 'incomplete',
        ])->assertRedirect(route('learn.lesson', [$s['tenant'], $s['lesson1']]));

        $row = LessonProgress::query()->where('lesson_id', $s['lesson1']->id)->where('user_id', $s['user']->id)->first();
        $this->assertNull($row->completed_at);
    }

    public function test_ajax_progress_ping_merges_percent_without_intent(): void
    {
        $s = $this->enrolledLearnerWithTwoLessons();
        $this->actingAs($s['user']);

        $this->post(route('learn.lesson.progress', [$s['tenant'], $s['lesson1']]), [
            'content_progress_percent' => 33,
            'position_seconds' => 12,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->assertOk()->assertJson(['ok' => true]);

        $row = LessonProgress::query()->where('lesson_id', $s['lesson1']->id)->where('user_id', $s['user']->id)->first();
        $this->assertNotNull($row);
        $this->assertSame(33, (int) $row->content_progress_percent);
        $this->assertSame(12, (int) $row->position_seconds);
    }

    public function test_progress_requires_enrollment(): void
    {
        $s = $this->enrolledLearnerWithTwoLessons();
        $stranger = User::factory()->create();
        $this->actingAs($stranger);

        $this->post(route('learn.lesson.progress', [$s['tenant'], $s['lesson1']]), [
            'intent' => 'save_notes',
            'notes' => 'X',
        ])->assertForbidden();
    }
}
