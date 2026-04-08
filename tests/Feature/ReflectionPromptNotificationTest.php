<?php

namespace Tests\Feature;

use App\Enums\TenantRole;
use App\Models\ReflectionPrompt;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\ReflectionPromptPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReflectionPromptNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_prompt_notifies_learners_in_space(): void
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

        $learner = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'role' => TenantRole::Learner->value,
        ]);

        ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => null,
            'title' => 'Day 1',
            'body' => 'What did you learn?',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Notification::assertSentTo($learner, ReflectionPromptPublishedNotification::class);
    }
}
