<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Booking\BookingSlotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SubmitBookingRequestAction
{
    public function __construct(
        private BookingSlotService $slots,
    ) {}

    /**
     * @return array{ok: true, booking: Booking}|array{ok: false, code: 'coach'|'slot'|'taken'}
     */
    public function handle(
        Tenant $tenant,
        int $coachUserId,
        Carbon $startsUtc,
        Carbon $endsUtc,
        ?User $user,
        ?string $guestName,
        ?string $guestEmail,
        ?string $guestPhone,
        ?string $bookerMessage,
    ): array {
        $coaches = $this->slots->bookableCoaches($tenant);
        if (! $coaches->contains('id', $coachUserId)) {
            Log::warning('booking.request.rejected', [
                'tenant_id' => $tenant->id,
                'coach_user_id' => $coachUserId,
                'code' => 'coach',
            ]);

            return ['ok' => false, 'code' => 'coach'];
        }

        $allowed = collect($this->slots->availableSlotsUtc($tenant, $coachUserId, null, null))
            ->contains(function (array $s) use ($startsUtc, $endsUtc): bool {
                return Carbon::parse($s['start'])->equalTo($startsUtc) && Carbon::parse($s['end'])->equalTo($endsUtc);
            });

        if (! $allowed) {
            Log::warning('booking.request.rejected', [
                'tenant_id' => $tenant->id,
                'coach_user_id' => $coachUserId,
                'code' => 'slot',
                'starts_at' => $startsUtc->toIso8601String(),
                'ends_at' => $endsUtc->toIso8601String(),
            ]);

            return ['ok' => false, 'code' => 'slot'];
        }

        $booking = DB::transaction(function () use ($tenant, $coachUserId, $startsUtc, $endsUtc, $user, $guestName, $guestEmail, $guestPhone, $bookerMessage) {
            if ($this->slots->overlapsExistingBooking($coachUserId, $startsUtc, $endsUtc)) {
                return null;
            }

            return Booking::query()->create([
                'tenant_id' => $tenant->id,
                'coach_user_id' => $coachUserId,
                'booker_user_id' => $user?->id,
                'guest_name' => $user ? null : $guestName,
                'guest_email' => $user ? null : $guestEmail,
                'guest_phone' => $user ? null : $guestPhone,
                'starts_at' => $startsUtc,
                'ends_at' => $endsUtc,
                'status' => BookingStatus::Pending,
                'booker_message' => $bookerMessage,
            ]);
        });

        if ($booking === null) {
            Log::warning('booking.request.rejected', [
                'tenant_id' => $tenant->id,
                'coach_user_id' => $coachUserId,
                'code' => 'taken',
                'starts_at' => $startsUtc->toIso8601String(),
                'ends_at' => $endsUtc->toIso8601String(),
            ]);

            return ['ok' => false, 'code' => 'taken'];
        }

        Log::info('booking.request.created', [
            'booking_id' => $booking->id,
            'tenant_id' => $tenant->id,
            'coach_user_id' => $coachUserId,
            'booker_user_id' => $user?->id,
            'guest' => $user === null,
            'starts_at' => $booking->starts_at?->toIso8601String(),
            'ends_at' => $booking->ends_at?->toIso8601String(),
            'status' => $booking->status->value,
        ]);

        return ['ok' => true, 'booking' => $booking];
    }
}
