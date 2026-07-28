<?php

namespace App\Actions\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Booking\BookingSlotService;
use Illuminate\Support\Facades\Log;

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
            Log::warning('booking.respond.rejected', [
                'booking_id' => $booking->id,
                'tenant_id' => $booking->tenant_id,
                'action' => 'confirm',
                'status' => $booking->status->value,
            ]);

            return ['ok' => false, 'warning' => 'Only pending requests can be confirmed.'];
        }

        if ($this->slots->overlapsExistingBooking($booking->coach_user_id, $booking->starts_at, $booking->ends_at, $booking->id)) {
            Log::warning('booking.respond.rejected', [
                'booking_id' => $booking->id,
                'tenant_id' => $booking->tenant_id,
                'action' => 'confirm',
                'code' => 'overlap',
            ]);

            return ['ok' => false, 'warning' => 'Another booking already holds this slot.'];
        }

        $booking->update([
            'status' => BookingStatus::Confirmed,
            'responded_at' => now(),
        ]);

        Log::info('booking.respond.confirmed', [
            'booking_id' => $booking->id,
            'tenant_id' => $booking->tenant_id,
            'coach_user_id' => $booking->coach_user_id,
        ]);

        return ['ok' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, warning: string}
     */
    public function decline(Booking $booking, ?string $coachInternalNote): array
    {
        if ($booking->status !== BookingStatus::Pending) {
            Log::warning('booking.respond.rejected', [
                'booking_id' => $booking->id,
                'tenant_id' => $booking->tenant_id,
                'action' => 'decline',
                'status' => $booking->status->value,
            ]);

            return ['ok' => false, 'warning' => 'Only pending requests can be declined.'];
        }

        $booking->update([
            'status' => BookingStatus::Declined,
            'coach_internal_note' => $coachInternalNote,
            'responded_at' => now(),
        ]);

        Log::info('booking.respond.declined', [
            'booking_id' => $booking->id,
            'tenant_id' => $booking->tenant_id,
            'coach_user_id' => $booking->coach_user_id,
        ]);

        return ['ok' => true];
    }
}
