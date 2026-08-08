<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\ReflectionPrompt;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ReflectionPromptPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReflectionAudienceNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_prompt_notifies_enrolled_user_without_learner_membership(): void
    {
        Notification::fake();

        $tenant = Tenant::query()->create([
            'name' => 'Notify space',
            'slug' => 'notify-space',
            'settings' => [
                'reflections' => [
                    'enabled' => true,
                    'notify_email' => true,
                    'notify_database' => true,
                ],
            ],
        ]);

        $enrolledOnly = User::factory()->create();
        $program = Program::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Program',
            'slug' => 'program',
            'is_published' => true,
            'sort_order' => 0,
        ]);
        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $enrolledOnly->id,
            'program_id' => $program->id,
            'status' => 'active',
        ]);

        ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => null,
            'title' => 'Day 1',
            'body' => 'What did you learn?',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Notification::assertSentTo($enrolledOnly, ReflectionPromptPublishedNotification::class);
    }
}
