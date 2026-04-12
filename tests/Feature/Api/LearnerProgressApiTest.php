<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Program;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LearnerProgressApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{tenant: Tenant, course: Course, lesson: Lesson}
     */
    private function publishedCourseWithLesson(): array
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
        $lesson = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'L',
            'slug' => 'l',
            'lesson_type' => 'text',
            'body' => 'Hi',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        return compact('tenant', 'course', 'lesson');
    }

    public function test_continue_returns_null_when_not_enrolled(): void
    {
        $data = $this->publishedCourseWithLesson();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/continue")
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_lesson_progress_forbidden_without_enrollment(): void
    {
        $data = $this->publishedCourseWithLesson();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/tenants/{$data['tenant']->slug}/lessons/{$data['lesson']->id}/progress", [
            'completed' => true,
        ])->assertForbidden();
    }

    public function test_lesson_progress_rejects_empty_payload(): void
    {
        $data = $this->publishedCourseWithLesson();
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'course_id' => $data['course']->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/tenants/{$data['tenant']->slug}/lessons/{$data['lesson']->id}/progress", [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Provide notes, notes_is_public, position_seconds, content_progress_percent, and/or completed.');
    }

    public function test_lesson_progress_accepts_root_lesson_without_module(): void
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
        $lesson = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => null,
            'title' => 'Root L',
            'slug' => 'root-l',
            'lesson_type' => 'text',
            'body' => 'Hi',
            'sort_order' => 1,
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
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/tenants/{$tenant->slug}/lessons/{$lesson->id}/progress", [
            'completed' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.lesson_id', $lesson->id);
    }

    public function test_lesson_progress_persists_notes_is_public_when_notes_present(): void
    {
        $data = $this->publishedCourseWithLesson();
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'course_id' => $data['course']->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/tenants/{$data['tenant']->slug}/lessons/{$data['lesson']->id}/progress", [
            'notes' => 'Visible to peers',
            'notes_is_public' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.notes_is_public', true);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $data['lesson']->id,
            'notes_is_public' => true,
        ]);

        $this->getJson("/api/v1/tenants/{$data['tenant']->slug}/courses/{$data['course']->id}")
            ->assertOk()
            ->assertJsonFragment(['notes_is_public' => true]);
    }

    public function test_lesson_progress_clears_notes_is_public_when_notes_cleared(): void
    {
        $data = $this->publishedCourseWithLesson();
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'course_id' => $data['course']->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        LessonProgress::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'lesson_id' => $data['lesson']->id,
            'notes' => 'Was public',
            'notes_is_public' => true,
        ]);

        $this->putJson("/api/v1/tenants/{$data['tenant']->slug}/lessons/{$data['lesson']->id}/progress", [
            'notes' => '',
        ])
            ->assertOk()
            ->assertJsonPath('data.notes_is_public', false);
    }

    public function test_lesson_progress_content_percent_is_high_water_and_returned_in_response(): void
    {
        $data = $this->publishedCourseWithLesson();
        $user = User::factory()->create();
        Enrollment::query()->create([
            'tenant_id' => $data['tenant']->id,
            'user_id' => $user->id,
            'course_id' => $data['course']->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/tenants/{$data['tenant']->slug}/lessons/{$data['lesson']->id}/progress", [
            'content_progress_percent' => 20,
        ])
            ->assertOk()
            ->assertJsonPath('data.content_progress_percent', 20);

        $this->putJson("/api/v1/tenants/{$data['tenant']->slug}/lessons/{$data['lesson']->id}/progress", [
            'content_progress_percent' => 55,
        ])
            ->assertOk()
            ->assertJsonPath('data.content_progress_percent', 55);

        $this->putJson("/api/v1/tenants/{$data['tenant']->slug}/lessons/{$data['lesson']->id}/progress", [
            'content_progress_percent' => 30,
        ])
            ->assertOk()
            ->assertJsonPath('data.content_progress_percent', 55);
    }
}
