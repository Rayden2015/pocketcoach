<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Program;
use App\Models\ReflectionPrompt;
use App\Models\ReflectionResponse;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PeerSharingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_public_notes_lists_only_public_from_other_learners(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't', 'status' => Tenant::STATUS_ACTIVE]);
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

        $viewer = User::factory()->create();
        $author = User::factory()->create(['name' => 'Note Author']);
        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $viewer->id,
            'course_id' => $course->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $author->id,
            'course_id' => $course->id,
            'source' => 'test',
            'status' => 'active',
        ]);

        LessonProgress::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $author->id,
            'lesson_id' => $lesson->id,
            'notes' => 'Shared thought',
            'notes_is_public' => true,
        ]);

        Sanctum::actingAs($viewer);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/lessons/{$lesson->id}/public-notes")
            ->assertOk()
            ->assertJsonPath('data.0.notes', 'Shared thought')
            ->assertJsonPath('data.0.user.name', 'Note Author');
    }

    public function test_reflection_public_responses_requires_reflections_enabled_and_published_prompt(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 'tr', 'status' => Tenant::STATUS_ACTIVE]);
        $tenant->forceFill([
            'settings' => array_merge($tenant->settings ?? [], [
                'reflections' => ['enabled' => true],
            ]),
        ])->save();

        $coach = User::factory()->create();
        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'Hi',
            'body' => 'Q',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $a = User::factory()->create(['name' => 'Alice']);
        $b = User::factory()->create(['name' => 'Bob']);

        ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $a->id,
            'body' => 'Public text',
            'is_public' => true,
            'first_submitted_at' => now(),
        ]);

        Sanctum::actingAs($b);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/reflection-prompts/{$prompt->id}/public-responses")
            ->assertOk()
            ->assertJsonPath('data.0.body', 'Public text')
            ->assertJsonPath('data.0.user.name', 'Alice');
    }
}
