<?php

namespace App\Http\Controllers\Web;

use App\Actions\Booking\SubmitBookingRequestAction;
use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Notifications\NewBookingRequestNotification;
use App\Services\Booking\BookingSlotService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    public function __construct(
        private BookingSlotService $slots,
        private SubmitBookingRequestAction $submitBooking,
    ) {}

    public function show(Request $request, Tenant $tenant): View
    {
        $coaches = $this->slots->bookableCoaches($tenant);
        $coachId = (int) $request->query('coach', 0);
        if ($coachId === 0 && $coaches->count() === 1) {
            $coachId = (int) $coaches->first()->id;
        }

        $slotPayload = [];
        if ($coachId > 0 && $coaches->contains('id', $coachId)) {
            $slotPayload = $this->slots->availableSlotsUtc(
                $tenant,
                $coachId,
                $request->query('from'),
                $request->query('to'),
            );
        }

        $user = $request->user();
        $staffCanConfigureBooking = false;
        if ($user !== null) {
            $membership = $user->memberships()->where('tenant_id', $tenant->id)->first();
            $staffCanConfigureBooking = $membership !== null
                && in_array($membership->role, TenantRole::staffValues(), true);
        }

        return view('public.booking', [
            'tenant' => $tenant,
            'coaches' => $coaches,
            'selectedCoachId' => $coachId,
            'slots' => $slotPayload,
            'staffCanConfigureBooking' => $staffCanConfigureBooking,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $guestRules = $request->user() === null ? [
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:64'],
        ] : [
            'guest_name' => ['nullable', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:64'],
        ];

        $validated = $request->validate(array_merge([
            'coach_user_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'booker_message' => ['nullable', 'string', 'max:2000'],
        ], $guestRules));

        $user = $request->user();
        $startsUtc = Carbon::parse($validated['starts_at'])->utc();
        $endsUtc = Carbon::parse($validated['ends_at'])->utc();

        $result = $this->submitBooking->handle(
            $tenant,
            (int) $validated['coach_user_id'],
            $startsUtc,
            $endsUtc,
            $user,
            $validated['guest_name'] ?? null,
            $validated['guest_email'] ?? null,
            $validated['guest_phone'] ?? null,
            $validated['booker_message'] ?? null,
        );

        if (! $result['ok']) {
            return match ($result['code']) {
                'coach' => back()->withErrors(['coach_user_id' => 'This coach is not accepting bookings.'])->withInput(),
                'slot' => back()->withErrors(['starts_at' => 'That time slot is no longer available.'])->withInput(),
                'taken' => back()->withErrors(['starts_at' => 'That time slot was just taken. Pick another.'])->withInput(),
            };
        }

        $booking = $result['booking'];

        $booking->load('coach');
        if ($booking->coach) {
            $booking->coach->notify(new NewBookingRequestNotification($booking));
        }

        return redirect()
            ->to($tenant->publicUrl('book?coach='.(int) $validated['coach_user_id']))
            ->with('status', 'Your booking request was sent. The coach will confirm or decline by email.');
    }
}
