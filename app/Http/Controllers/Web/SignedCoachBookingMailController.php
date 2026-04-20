<?php

namespace App\Http\Controllers\Web;

use App\Actions\Booking\CoachRespondToBookingRequestAction;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;

/**
 * Guest-safe confirm/decline via signed URLs embedded in coach notification email.
 */
class SignedCoachBookingMailController extends Controller
{
    public function __construct(
        private CoachRespondToBookingRequestAction $respond,
    ) {}

    public function confirm(Tenant $tenant, Booking $booking): RedirectResponse
    {
        $this->assertBookingInTenant($tenant, $booking);

        $result = $this->respond->confirm($booking);

        if (! $result['ok']) {
            return redirect()
                ->route('booking.mail.result', ['tenant' => $tenant, 'outcome' => 'none'])
                ->with('warning', $result['warning']);
        }

        return redirect()
            ->route('booking.mail.result', ['tenant' => $tenant, 'outcome' => 'confirmed'])
            ->with('status', 'Booking confirmed. The learner will see the confirmed time in this space.');
    }

    public function decline(Tenant $tenant, Booking $booking): RedirectResponse
    {
        $this->assertBookingInTenant($tenant, $booking);

        $result = $this->respond->decline($booking, null);

        if (! $result['ok']) {
            return redirect()
                ->route('booking.mail.result', ['tenant' => $tenant, 'outcome' => 'none'])
                ->with('warning', $result['warning']);
        }

        return redirect()
            ->route('booking.mail.result', ['tenant' => $tenant, 'outcome' => 'declined'])
            ->with('status', 'Booking declined. You can still review requests after signing in to the coach console.');
    }

    private function assertBookingInTenant(Tenant $tenant, Booking $booking): void
    {
        abort_unless($booking->tenant_id === $tenant->id, 404);
    }
}
