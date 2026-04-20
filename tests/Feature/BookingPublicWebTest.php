<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CoachBookingSetting;
use App\Models\CoachWeeklyAvailability;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Notifications\NewBookingRequestNotification;
use App\Services\Booking\BookingSlotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BookingPublicWebTest extends TestCase
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

    public function test_public_book_page_loads(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'book-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $coach = User::factory()->create(['timezone' => 'UTC']);
        $this->seedBookableCoach($tenant, $coach);

        $this->get(route('public.book', $tenant))
            ->assertOk()
            ->assertSee('Book a coach', false);
    }

    public function test_guest_booking_requires_contact_and_holds_slot(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'book-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $coach = User::factory()->create(['timezone' => 'UTC']);
        $this->seedBookableCoach($tenant, $coach);

        $slots = app(BookingSlotService::class)->availableSlotsUtc($tenant, $coach->id, '2026-04-15', '2026-04-15');
        $this->assertNotEmpty($slots);
        $pick = $slots[0];

        $this->post(route('public.book.store', $tenant), [
            'coach_user_id' => $coach->id,
            'starts_at' => $pick['start'],
            'ends_at' => $pick['end'],
            'guest_name' => 'Alex Guest',
            'guest_email' => 'alex@example.com',
            'guest_phone' => '+15551212',
        ])->assertRedirect(route('public.book', $tenant).'?coach='.$coach->id);

        $this->assertDatabaseHas('bookings', [
            'tenant_id' => $tenant->id,
            'coach_user_id' => $coach->id,
            'guest_email' => 'alex@example.com',
            'status' => 'pending',
        ]);

        Notification::assertSentTo($coach, NewBookingRequestNotification::class);

        $this->post(route('public.book.store', $tenant), [
            'coach_user_id' => $coach->id,
            'starts_at' => $pick['start'],
            'ends_at' => $pick['end'],
            'guest_name' => 'Other',
            'guest_email' => 'other@example.com',
            'guest_phone' => '+15559999',
        ])->assertSessionHasErrors('starts_at');
    }

    public function test_coach_can_confirm_pending_booking(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'book-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $coach = User::factory()->create(['timezone' => 'UTC']);
        $this->seedBookableCoach($tenant, $coach);

        $slots = app(BookingSlotService::class)->availableSlotsUtc($tenant, $coach->id, '2026-04-15', '2026-04-15');
        $pick = $slots[0];

        $this->post(route('public.book.store', $tenant), [
            'coach_user_id' => $coach->id,
            'starts_at' => $pick['start'],
            'ends_at' => $pick['end'],
            'guest_name' => 'Alex Guest',
            'guest_email' => 'alex@example.com',
            'guest_phone' => '+15551212',
        ]);

        $bookingId = (int) Booking::query()->where('coach_user_id', $coach->id)->value('id');

        $this->actingAs($coach)
            ->post(route('coach.bookings.confirm', [$tenant, $bookingId]))
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => 'confirmed',
        ]);
    }

    public function test_coach_can_confirm_pending_booking_via_signed_email_link(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'book-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $coach = User::factory()->create(['timezone' => 'UTC']);
        $this->seedBookableCoach($tenant, $coach);

        $slots = app(BookingSlotService::class)->availableSlotsUtc($tenant, $coach->id, '2026-04-15', '2026-04-15');
        $pick = $slots[0];

        $this->post(route('public.book.store', $tenant), [
            'coach_user_id' => $coach->id,
            'starts_at' => $pick['start'],
            'ends_at' => $pick['end'],
            'guest_name' => 'Alex Guest',
            'guest_email' => 'alex@example.com',
            'guest_phone' => '+15551212',
        ]);

        $booking = Booking::query()->where('coach_user_id', $coach->id)->sole();

        $signed = URL::temporarySignedRoute(
            'mail.booking.confirm',
            now()->addDay(),
            ['tenant' => $tenant, 'booking' => $booking],
        );

        $this->get($signed)
            ->assertRedirect(route('booking.mail.result', ['tenant' => $tenant, 'outcome' => 'confirmed']));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_expired_signed_booking_link_returns_403(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00', 'UTC'));

        $tenant = Tenant::query()->create([
            'name' => 'Space',
            'slug' => 'book-space',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $coach = User::factory()->create(['timezone' => 'UTC']);
        $this->seedBookableCoach($tenant, $coach);

        $slots = app(BookingSlotService::class)->availableSlotsUtc($tenant, $coach->id, '2026-04-15', '2026-04-15');
        $pick = $slots[0];

        $this->post(route('public.book.store', $tenant), [
            'coach_user_id' => $coach->id,
            'starts_at' => $pick['start'],
            'ends_at' => $pick['end'],
            'guest_name' => 'Alex Guest',
            'guest_email' => 'alex@example.com',
            'guest_phone' => '+15551212',
        ]);

        $booking = Booking::query()->where('coach_user_id', $coach->id)->sole();

        $signed = URL::temporarySignedRoute(
            'mail.booking.confirm',
            now()->subMinute(),
            ['tenant' => $tenant, 'booking' => $booking],
        );

        $this->get($signed)->assertForbidden();
    }
}
