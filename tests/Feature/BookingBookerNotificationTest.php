<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CoachBookingSetting;
use App\Models\CoachWeeklyAvailability;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\BookingDeclinedNotification;
use App\Notifications\BookingRequestReceivedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingBookerNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function seedBookableCoach(Tenant $tenant, User $coach): void
    {
        TenantMembership::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $coach->id,
            'role' => 'owner',
        ]);

        CoachBookingSetting::query()->create([
            'tenant_id' => $tenant->id,
            'coach_user_id' => $coach->id,
            'enabled' => true,
            'slot_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'min_notice_hours' => 0,
            'max_advance_days' => 30,
            'timezone' => 'UTC',
        ]);

        CoachWeeklyAvailability::query()->create([
            'tenant_id' => $tenant->id,
            'coach_user_id' => $coach->id,
            'day_of_week' => 3,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);
    }

    public function test_booker_receives_request_received_notification(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'book-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $coach = User::factory()->create(['timezone' => 'UTC']);
        $booker = User::factory()->create();
        $this->seedBookableCoach($tenant, $coach);

        $slots = app(\App\Services\Booking\BookingSlotService::class)
            ->availableSlotsUtc($tenant, $coach->id, null, null);
        $slot = $slots[0];

        $this->actingAs($booker)->post(route('public.book.store', $tenant), [
            'coach_user_id' => $coach->id,
            'starts_at' => $slot['start'],
            'ends_at' => $slot['end'],
        ])->assertRedirect();

        Notification::assertSentTo($booker, BookingRequestReceivedNotification::class);
    }

    public function test_booker_receives_confirmed_and_declined_notifications(): void
    {
        Notification::fake();

        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'book-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $coach = User::factory()->create();
        $booker = User::factory()->create();
        $this->seedBookableCoach($tenant, $coach);

        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'coach_user_id' => $coach->id,
            'booker_user_id' => $booker->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'status' => \App\Enums\BookingStatus::Pending,
        ]);

        $this->actingAs($coach)->post(route('coach.bookings.confirm', [$tenant, $booking]))
            ->assertRedirect();

        Notification::assertSentTo($booker, BookingConfirmedNotification::class);

        $declined = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'coach_user_id' => $coach->id,
            'booker_user_id' => $booker->id,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addMinutes(30),
            'status' => \App\Enums\BookingStatus::Pending,
        ]);

        $this->actingAs($coach)->post(route('coach.bookings.decline', [$tenant, $declined]))
            ->assertRedirect();

        Notification::assertSentTo($booker, BookingDeclinedNotification::class);
    }
}
