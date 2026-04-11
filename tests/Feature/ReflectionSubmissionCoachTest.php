<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Program;
use App\Models\ReflectionPrompt;
use App\Models\ReflectionResponse;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ReflectionSubmissionCoachTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'guest-sp', 'status' => Tenant::STATUS_ACTIVE]);

        $this->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections']))
            ->assertRedirect(route('login'));
    }

    public function test_coach_sees_learner_reflection_submissions(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create(['name' => 'Coach']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $learner = User::factory()->create(['name' => 'Learner One', 'email' => 'learner@example.com']);

        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'Weekend reset',
            'body' => 'What will you **let go** of this week?',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $learner->id,
            'body' => 'I will focus on sleep and boundaries.',
            'first_submitted_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($coach)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections']));

        $response->assertOk();
        $response->assertSee('Learner One', false);
        $response->assertSee('learner@example.com', false);
        $response->assertSee('Weekend reset', false);
        $response->assertSee('I will focus on sleep and boundaries.', false);
    }

    public function test_non_staff_cannot_view_submissions(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp2', 'status' => Tenant::STATUS_ACTIVE]);
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections']))
            ->assertForbidden();
    }

    public function test_learner_role_member_cannot_view_submissions(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp-learner', 'status' => Tenant::STATUS_ACTIVE]);
        $learnerMember = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learnerMember->id,
            'role' => 'learner',
        ]);

        $this->actingAs($learnerMember)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections']))
            ->assertForbidden();
    }

    public function test_responses_from_other_tenants_are_not_listed(): void
    {
        $tenantA = Tenant::query()->create(['name' => 'A', 'slug' => 'ta', 'status' => Tenant::STATUS_ACTIVE]);
        $tenantB = Tenant::query()->create(['name' => 'B', 'slug' => 'tb', 'status' => Tenant::STATUS_ACTIVE]);

        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenantA->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $promptB = ReflectionPrompt::query()->create([
            'tenant_id' => $tenantB->id,
            'author_id' => null,
            'title' => 'Other space prompt',
            'body' => 'Secret',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $learner = User::factory()->create();
        ReflectionResponse::query()->create([
            'reflection_prompt_id' => $promptB->id,
            'user_id' => $learner->id,
            'body' => 'Should not appear for coach A.',
            'first_submitted_at' => now(),
        ]);

        $response = $this->actingAs($coach)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenantA, 'tab' => 'reflections']));

        $response->assertOk();
        $response->assertDontSee('Should not appear for coach A.', false);
        $response->assertDontSee('Other space prompt', false);
    }

    public function test_filter_by_prompt_query_shows_only_matching_submissions(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp-filter', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $promptOne = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'Prompt Alpha',
            'body' => 'Body one',
            'is_published' => true,
            'published_at' => now(),
        ]);
        $promptTwo = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'Prompt Beta',
            'body' => 'Body two',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $learner = User::factory()->create();
        ReflectionResponse::query()->create([
            'reflection_prompt_id' => $promptOne->id,
            'user_id' => $learner->id,
            'body' => 'Answer for alpha only.',
            'first_submitted_at' => now(),
        ]);
        $otherLearner = User::factory()->create();
        ReflectionResponse::query()->create([
            'reflection_prompt_id' => $promptTwo->id,
            'user_id' => $otherLearner->id,
            'body' => 'Answer for beta only.',
            'first_submitted_at' => now(),
        ]);

        $filtered = $this->actingAs($coach)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections', 'prompt' => $promptOne->id]));

        $filtered->assertOk();
        $filtered->assertSee('Answer for alpha only.', false);
        $filtered->assertDontSee('Answer for beta only.', false);

        $all = $this->actingAs($coach)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections']));

        $all->assertOk();
        $all->assertSee('Answer for alpha only.', false);
        $all->assertSee('Answer for beta only.', false);
    }

    public function test_non_numeric_prompt_query_is_ignored_and_lists_all(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp-bad', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'Solo',
            'body' => 'X',
            'is_published' => true,
            'published_at' => now(),
        ]);
        ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'Listed anyway.',
            'first_submitted_at' => now(),
        ]);

        $this->actingAs($coach)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections', 'prompt' => 'not-a-number']))
            ->assertOk()
            ->assertSee('Listed anyway.', false);
    }

    public function test_submissions_are_ordered_by_updated_at_descending(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp-order', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'One prompt',
            'body' => 'Shared',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $older = ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'Older submission.',
            'first_submitted_at' => now()->subHours(3),
        ]);
        $older->forceFill(['updated_at' => now()->subHours(2)])->saveQuietly();

        $newer = ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'Newer submission.',
            'first_submitted_at' => now()->subHour(),
        ]);
        $newer->forceFill(['updated_at' => now()])->saveQuietly();

        $response = $this->actingAs($coach)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections']));

        $response->assertOk();
        /** @var LengthAwarePaginator $paginator */
        $paginator = $response->viewData('responses');
        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $items = $paginator->items();
        $this->assertCount(2, $items);
        $this->assertSame($newer->id, $items[0]->id);
        $this->assertSame($older->id, $items[1]->id);
    }

    public function test_pagination_splits_results_at_25_per_page(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp-page', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'Bulk',
            'body' => 'Y',
            'is_published' => true,
            'published_at' => now(),
        ]);

        for ($i = 0; $i < 26; $i++) {
            ReflectionResponse::query()->create([
                'reflection_prompt_id' => $prompt->id,
                'user_id' => User::factory()->create()->id,
                'body' => 'Response '.$i,
                'first_submitted_at' => now(),
            ]);
        }

        $page1 = $this->actingAs($coach)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections']));

        $page1->assertOk();
        /** @var LengthAwarePaginator $paginator */
        $paginator = $page1->viewData('responses');
        $this->assertSame(26, $paginator->total());
        $this->assertCount(25, $paginator->items());
        $this->assertTrue($paginator->hasMorePages());

        $page2 = $this->actingAs($coach)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections', 'page' => 2]));

        $page2->assertOk();
        /** @var LengthAwarePaginator $p2 */
        $p2 = $page2->viewData('responses');
        $this->assertCount(1, $p2->items());
    }

    public function test_coach_lesson_notes_tab_lists_lesson_progress_with_notes(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp-lesson-tab', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

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
            'title' => 'L1',
            'slug' => 'l1',
            'lesson_type' => 'text',
            'body' => 'X',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $learner = User::factory()->create(['name' => 'Note Writer']);
        LessonProgress::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'lesson_id' => $lesson->id,
            'notes' => 'My private lesson thought',
            'notes_is_public' => false,
        ]);

        $response = $this->actingAs($coach)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'lessons']));

        $response->assertOk();
        $response->assertSee('Lesson notes', false);
        $response->assertSee('Note Writer', false);
        $response->assertSee('My private lesson thought', false);
        $response->assertSee('L1', false);
    }

    public function test_selected_prompt_id_passed_to_view_when_filtering(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'sp-selected', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'P',
            'body' => 'B',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($coach)
            ->get(route('coach.learner-submissions.index', ['tenant' => $tenant, 'tab' => 'reflections', 'prompt' => $prompt->id]));

        $response->assertOk();
        $this->assertSame($prompt->id, $response->viewData('selectedPromptId'));
    }
}
