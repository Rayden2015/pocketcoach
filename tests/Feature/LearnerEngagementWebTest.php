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
use App\Models\SubmissionConversationMessage;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP/UI coverage for learner submissions, coach conversations, and related Blade (no Dusk).
 */
class LearnerEngagementWebTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{
     *   tenant: Tenant,
     *   coach: User,
     *   learner: User,
     *   prompt: ReflectionPrompt,
     *   reflectionResponse: ReflectionResponse
     * }
     */
    private function seedReflectionSubmission(): array
    {
        $tenant = Tenant::query()->create(['name' => 'Eng', 'slug' => 'eng-r', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create(['name' => 'Coach Eng']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        $learner = User::factory()->create(['name' => 'Learner Eng']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);
        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'Weekly check-in',
            'body' => 'How are you?',
            'is_published' => true,
            'published_at' => now(),
        ]);
        $reflectionResponse = ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $learner->id,
            'body' => 'Doing well.',
            'first_submitted_at' => now(),
        ]);

        return compact('tenant', 'coach', 'learner', 'prompt', 'reflectionResponse');
    }

    /**
     * @return array{
     *   tenant: Tenant,
     *   coach: User,
     *   learner: User,
     *   lesson: Lesson,
     *   course: Course,
     *   lessonProgress: LessonProgress
     * }
     */
    private function seedLessonNotesSubmission(): array
    {
        $tenant = Tenant::query()->create(['name' => 'Eng', 'slug' => 'eng-l', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        $learner = User::factory()->create(['name' => 'Notes Learner']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Prog',
            'slug' => 'prog',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'Course Eng',
            'slug' => 'course-eng',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'Mod',
            'slug' => 'mod',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $lesson = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'Lesson Eng',
            'slug' => 'lesson-eng',
            'lesson_type' => 'text',
            'body' => 'Content',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'source' => 'test',
            'status' => 'active',
        ]);
        $lessonProgress = LessonProgress::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'lesson_id' => $lesson->id,
            'notes' => 'Lesson note body',
            'notes_is_public' => false,
        ]);

        return compact('tenant', 'coach', 'learner', 'lesson', 'course', 'lessonProgress');
    }

    public function test_coach_reflections_tab_shows_conversation_open_link_and_count(): void
    {
        $s = $this->seedReflectionSubmission();
        SubmissionConversationMessage::query()->create([
            'tenant_id' => $s['tenant']->id,
            'subject_type' => $s['reflectionResponse']->getMorphClass(),
            'subject_id' => $s['reflectionResponse']->id,
            'user_id' => $s['coach']->id,
            'parent_id' => null,
            'body' => 'Hi there',
        ]);

        $url = route('submission-conversations.reflection.show', [$s['tenant'], $s['reflectionResponse']]);

        $this->actingAs($s['coach'])
            ->get(route('coach.learner-submissions.index', ['tenant' => $s['tenant'], 'tab' => 'reflections']))
            ->assertOk()
            ->assertSee('Conversation', false)
            ->assertSee('Open', false)
            ->assertSee('(1)', false)
            ->assertSee($url, false);
    }

    public function test_coach_lessons_tab_shows_conversation_open_link(): void
    {
        $s = $this->seedLessonNotesSubmission();

        $url = route('submission-conversations.lesson.show', [$s['tenant'], $s['lessonProgress']]);

        $this->actingAs($s['coach'])
            ->get(route('coach.learner-submissions.index', ['tenant' => $s['tenant'], 'tab' => 'lessons']))
            ->assertOk()
            ->assertSee('Lesson notes', false)
            ->assertSee('Notes Learner', false)
            ->assertSee('Open', false)
            ->assertSee($url, false);
    }

    public function test_conversation_reflection_page_shows_original_submission_thread_and_enter_hint(): void
    {
        $s = $this->seedReflectionSubmission();

        $this->actingAs($s['coach'])
            ->get(route('submission-conversations.reflection.show', [$s['tenant'], $s['reflectionResponse']]))
            ->assertOk()
            ->assertSee('Original submission', false)
            ->assertSee('Doing well.', false)
            ->assertSee('Messages', false)
            ->assertSee('main-reply-form', false)
            ->assertSee('Press', false)
            ->assertSee('Enter', false)
            ->assertSee('Send', false);
    }

    public function test_learn_reflection_page_shows_coach_conversation_card_when_response_exists(): void
    {
        $s = $this->seedReflectionSubmission();

        $threadUrl = route('submission-conversations.reflection.show', [$s['tenant'], $s['reflectionResponse']]);

        $this->actingAs($s['learner'])
            ->get(route('learn.reflections.show', [$s['tenant'], $s['prompt']]))
            ->assertOk()
            ->assertSee('Coach conversation', false)
            ->assertSee('Open conversation', false)
            ->assertSee($threadUrl, false);
    }

    public function test_learn_reflection_page_prompts_to_save_first_when_no_response_row(): void
    {
        $tenant = Tenant::query()->create(['name' => 'E', 'slug' => 'e-nr', 'status' => Tenant::STATUS_ACTIVE]);
        $u = User::factory()->create();
        $coach = User::factory()->create();
        TenantMembership::query()->create(['tenant_id' => $tenant->id, 'user_id' => $u->id, 'role' => 'learner']);
        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'T',
            'body' => 'B',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($u)
            ->get(route('learn.reflections.show', [$tenant, $prompt]))
            ->assertOk()
            ->assertSee('Save your reflection above', false);
    }

    public function test_learn_lesson_page_shows_coach_conversation_when_progress_exists(): void
    {
        $s = $this->seedLessonNotesSubmission();
        $threadUrl = route('submission-conversations.lesson.show', [$s['tenant'], $s['lessonProgress']]);

        $this->actingAs($s['learner'])
            ->get(route('learn.lesson', [$s['tenant'], $s['lesson']]))
            ->assertOk()
            ->assertSee('Coach conversation', false)
            ->assertSee('Open conversation', false)
            ->assertSee($threadUrl, false);
    }
}
