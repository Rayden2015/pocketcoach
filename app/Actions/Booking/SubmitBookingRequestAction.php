<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Booking\BookingSlotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
            return ['ok' => false, 'code' => 'coach'];
        }

        $allowed = collect($this->slots->availableSlotsUtc($tenant, $coachUserId, null, null))
            ->contains(function (array $s) use ($startsUtc, $endsUtc): bool {
                return Carbon::parse($s['start'])->equalTo($startsUtc) && Carbon::parse($s['end'])->equalTo($endsUtc);
            });

        if (! $allowed) {
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
            return ['ok' => false, 'code' => 'taken'];
        }

        return ['ok' => true, 'booking' => $booking];
    }
}
