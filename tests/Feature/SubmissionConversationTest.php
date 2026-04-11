<?php

namespace Tests\Feature;

use App\Models\Course;
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
use App\Notifications\SubmissionConversationMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubmissionConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_and_learner_can_exchange_messages_on_reflection(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'conv-r', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create(['name' => 'Coach']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $learner = User::factory()->create(['name' => 'Learner']);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);

        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'Daily',
            'body' => 'Think',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $learner->id,
            'body' => 'My reflection',
            'first_submitted_at' => now(),
        ]);

        $this->actingAs($coach)
            ->post(route('submission-conversations.reflection.message', [$tenant, $response]), [
                'body' => 'Great insight!',
                'parent_id' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('submission_conversation_messages', [
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'subject_type' => $response->getMorphClass(),
            'subject_id' => $response->id,
        ]);

        $msg = SubmissionConversationMessage::query()->firstOrFail();

        $this->actingAs($learner)
            ->post(route('submission-conversations.reflection.message', [$tenant, $response]), [
                'body' => 'Thanks coach',
                'parent_id' => $msg->id,
            ])
            ->assertRedirect();

        $this->assertEquals(2, SubmissionConversationMessage::query()->count());

        $this->actingAs($learner)
            ->get(route('submission-conversations.reflection.show', [$tenant, $response]))
            ->assertOk()
            ->assertSee('Great insight!', false)
            ->assertSee('Thanks coach', false);
    }

    public function test_other_learner_cannot_view_reflection_conversation(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'conv-x', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $learnerA = User::factory()->create();
        $learnerB = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learnerA->id,
            'role' => 'learner',
        ]);
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learnerB->id,
            'role' => 'learner',
        ]);

        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'T',
            'body' => 'B',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $rr = ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $learnerA->id,
            'body' => 'Private',
            'first_submitted_at' => now(),
        ]);

        $this->actingAs($learnerB)
            ->get(route('submission-conversations.reflection.show', [$tenant, $rr]))
            ->assertForbidden();
    }

    public function test_coach_and_learner_can_chat_on_lesson_notes(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'conv-l', 'status' => Tenant::STATUS_ACTIVE]);
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
            'sort_order' => 0,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M',
            'slug' => 'm',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $lesson = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'L',
            'slug' => 'l',
            'lesson_type' => 'text',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        $learner = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);

        $progress = LessonProgress::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'lesson_id' => $lesson->id,
            'notes' => 'My notes',
        ]);

        $this->actingAs($coach)
            ->post(route('submission-conversations.lesson.message', [$tenant, $progress]), [
                'body' => 'Feedback on notes',
            ])
            ->assertRedirect();

        $this->actingAs($learner)
            ->get(route('submission-conversations.lesson.show', [$tenant, $progress]))
            ->assertOk()
            ->assertSee('Feedback on notes', false);
    }

    public function test_api_lists_reflection_conversation_messages(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'api-r', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        $learner = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);

        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'T',
            'body' => 'B',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $rr = ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $learner->id,
            'body' => 'X',
            'first_submitted_at' => now(),
        ]);

        Sanctum::actingAs($learner);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/reflection-responses/{$rr->id}/conversation-messages")
            ->assertOk()
            ->assertJsonPath('data', []);

        Sanctum::actingAs($coach);

        $this->postJson("/api/v1/tenants/{$tenant->slug}/reflection-responses/{$rr->id}/conversation-messages", [
            'body' => 'API hello',
        ])->assertCreated();

        Sanctum::actingAs($learner);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/reflection-responses/{$rr->id}/conversation-messages")
            ->assertOk()
            ->assertJsonPath('data.0.body', 'API hello');
    }

    public function test_api_lesson_conversation_messages_index_and_store(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'api-l', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        $learner = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);

        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'P',
            'slug' => 'p',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'program_id' => $program->id,
            'title' => 'C',
            'slug' => 'c',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $module = Module::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'title' => 'M',
            'slug' => 'm',
            'sort_order' => 0,
            'is_published' => true,
        ]);
        $lesson = Lesson::query()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'module_id' => $module->id,
            'title' => 'L',
            'slug' => 'l',
            'lesson_type' => 'text',
            'sort_order' => 0,
            'is_published' => true,
        ]);

        $progress = LessonProgress::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'lesson_id' => $lesson->id,
            'notes' => 'N',
        ]);

        Sanctum::actingAs($learner);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/lesson-progress/{$progress->id}/conversation-messages")
            ->assertOk()
            ->assertJsonPath('data', []);

        $this->postJson("/api/v1/tenants/{$tenant->slug}/lesson-progress/{$progress->id}/conversation-messages", [
            'body' => 'From learner API',
        ])->assertCreated()
            ->assertJsonPath('data.body', 'From learner API');

        Sanctum::actingAs($coach);

        $this->getJson("/api/v1/tenants/{$tenant->slug}/lesson-progress/{$progress->id}/conversation-messages")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_web_post_rejects_invalid_parent_id(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'bad-p', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        $learner = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);
        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'T',
            'body' => 'B',
            'is_published' => true,
            'published_at' => now(),
        ]);
        $rr = ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $learner->id,
            'body' => 'X',
            'first_submitted_at' => now(),
        ]);

        $this->actingAs($coach)
            ->post(route('submission-conversations.reflection.message', [$tenant, $rr]), [
                'body' => 'Bad parent',
                'parent_id' => 999_999,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_api_post_rejects_invalid_parent_id(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'api-bad', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        $learner = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);
        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'T',
            'body' => 'B',
            'is_published' => true,
            'published_at' => now(),
        ]);
        $rr = ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $learner->id,
            'body' => 'X',
            'first_submitted_at' => now(),
        ]);

        Sanctum::actingAs($coach);

        $this->postJson("/api/v1/tenants/{$tenant->slug}/reflection-responses/{$rr->id}/conversation-messages", [
            'body' => 'Hi',
            'parent_id' => 999_999,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id']);
    }

    public function test_coach_message_fires_database_notification_to_learner(): void
    {
        Notification::fake();

        $tenant = Tenant::query()->create(['name' => 'Space', 'slug' => 'notif', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);
        $learner = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => 'learner',
        ]);
        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'T',
            'body' => 'B',
            'is_published' => true,
            'published_at' => now(),
        ]);
        $rr = ReflectionResponse::query()->create([
            'reflection_prompt_id' => $prompt->id,
            'user_id' => $learner->id,
            'body' => 'X',
            'first_submitted_at' => now(),
        ]);

        $this->actingAs($coach)
            ->post(route('submission-conversations.reflection.message', [$tenant, $rr]), [
                'body' => 'Coach note',
            ])
            ->assertRedirect();

        Notification::assertSentTo($learner, SubmissionConversationMessageNotification::class);
    }
}
