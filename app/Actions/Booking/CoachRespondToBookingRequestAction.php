<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Booking\BookingSlotService;

final class CoachRespondToBookingRequestAction
{
    public function __construct(
        private BookingSlotService $slots,
    ) {}

    /**
     * @return array{ok: true}|array{ok: false, warning: string}
     */
    public function confirm(Booking $booking): array
    {
        if ($booking->status !== BookingStatus::Pending) {
            return ['ok' => false, 'warning' => 'Only pending requests can be confirmed.'];
        }

        if ($this->slots->overlapsExistingBooking($booking->coach_user_id, $booking->starts_at, $booking->ends_at, $booking->id)) {
            return ['ok' => false, 'warning' => 'Another booking already holds this slot.'];
        }

        $booking->update([
            'status' => BookingStatus::Confirmed,
            'responded_at' => now(),
        ]);

        return ['ok' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, warning: string}
     */
    public function decline(Booking $booking, ?string $coachInternalNote): array
    {
        if ($booking->status !== BookingStatus::Pending) {
            return ['ok' => false, 'warning' => 'Only pending requests can be declined.'];
        }

        $booking->update([
            'status' => BookingStatus::Declined,
            'coach_internal_note' => $coachInternalNote,
            'responded_at' => now(),
        ]);

        return ['ok' => true];
    }
}
