<?php

namespace Tests\Feature;

use App\Models\ReflectionPrompt;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReflectionScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_create_scheduled_reflection_at_seven_am(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $target = Carbon::now(config('app.timezone'))->addDay()->setTime(7, 0, 0);

        $this->actingAs($coach)
            ->post(route('coach.reflections.store', $tenant), [
                'title' => 'Morning check-in',
                'body' => 'What is one priority today?',
                'publish_timing' => 'schedule',
                'scheduled_date' => $target->format('Y-m-d'),
                'scheduled_time' => '07:00',
            ])
            ->assertRedirect(route('coach.reflections.index', $tenant));

        $prompt = ReflectionPrompt::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($prompt);
        $this->assertFalse($prompt->is_published);
        $this->assertNull($prompt->published_at);
        $this->assertNotNull($prompt->scheduled_publish_at);
        $this->assertTrue($prompt->scheduled_publish_at->copy()->timezone(config('app.timezone'))->format('H:i') === '07:00');
    }

    public function test_coach_reflection_edit_resolves_route_model_binding(): void
    {
        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 'adeola', 'status' => Tenant::STATUS_ACTIVE]);
        $coach = User::factory()->create();
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        $prompt = ReflectionPrompt::query()->create([
            'tenant_id' => $tenant->id,
            'author_id' => $coach->id,
            'title' => 'Morning check-in',
            'body' => 'What is one priority today?',
            'is_published' => true,
            'published_at' => now(),
            'scheduled_publish_at' => null,
        ]);

        $this->actingAs($coach)
            ->get(route('coach.reflections.edit', [$tenant, $prompt]))
            ->assertOk()
            ->assertSee('Morning check-in', false)
            ->assertSee('← Reflections', false);
    }

    public function test_publish_due_command_publishes_scheduled_prompt(): void
    {
        Notification::fake();

        $tenant = Tenant::query()->create(['name' => 'T', 'slug' => 't', 'status' => Tenant::STATUS_ACTIVE]);
        $past = Carbon::now()->subMinute();

        $prompt = ReflectionPrompt::withoutEvents(function () use ($tenant, $past) {
            return ReflectionPrompt::query()->create([
                'tenant_id' => $tenant->id,
                'author_id' => null,
                'title' => 'Due',
                'body' => 'Hello',
                'is_published' => false,
                'published_at' => null,
                'scheduled_publish_at' => $past,
            ]);
        });

        Artisan::call('reflections:publish-due');

        $prompt->refresh();
        $this->assertTrue($prompt->is_published);
        $this->assertNotNull($prompt->published_at);
        Notification::assertNothingSent();
    }
}
