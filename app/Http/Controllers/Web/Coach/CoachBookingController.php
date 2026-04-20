<?php

namespace App\Http\Controllers\Web\Coach;

use App\Actions\Booking\CoachRespondToBookingRequestAction;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CoachBookingSetting;
use App\Models\CoachWeeklyAvailability;
use App\Models\Tenant;
use App\Services\Booking\BookingSlotService;
use App\Support\BookingTimezoneCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CoachBookingController extends Controller
{
    public function __construct(
        private BookingSlotService $slots,
        private CoachRespondToBookingRequestAction $respondToBooking,
    ) {}

    public function index(Tenant $tenant): View
    {
        $bookings = Booking::query()
            ->where('tenant_id', $tenant->id)
            ->where('coach_user_id', auth()->id())
            ->with(['booker'])
            ->orderByDesc('starts_at')
            ->paginate(30);

        return view('coach.bookings.index', [
            'tenant' => $tenant,
            'bookings' => $bookings,
        ]);
    }

    public function editSettings(Tenant $tenant): View
    {
        $settings = CoachBookingSetting::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'coach_user_id' => auth()->id(),
            ],
            [
                'enabled' => false,
                'slot_duration_minutes' => 30,
                'buffer_minutes' => 0,
                'min_notice_hours' => 2,
                'max_advance_days' => 21,
            ],
        );

        $availability = CoachWeeklyAvailability::query()
            ->where('tenant_id', $tenant->id)
            ->where('coach_user_id', auth()->id())
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $savedTz = old('timezone', $settings->timezone);

        return view('coach.bookings.settings', [
            'tenant' => $tenant,
            'settings' => $settings,
            'availability' => $availability,
            'timezoneChoices' => BookingTimezoneCatalog::selectOptionsIncluding(
                is_string($savedTz) ? $savedTz : null,
            ),
            'weekdayLabels' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ]);
    }

    public function updateSettings(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'slot_duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'min_notice_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'max_advance_days' => ['required', 'integer', 'min:1', 'max:90'],
            'timezone' => ['nullable', 'string', Rule::in(array_merge([''], BookingTimezoneCatalog::allowedIdentifiers()))],
        ]);

        $tz = $validated['timezone'] ?? null;
        if ($tz === '') {
            $tz = null;
        }

        CoachBookingSetting::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'coach_user_id' => auth()->id(),
            ],
            [
                'enabled' => $validated['enabled'],
                'slot_duration_minutes' => $validated['slot_duration_minutes'],
                'buffer_minutes' => $validated['buffer_minutes'],
                'min_notice_hours' => $validated['min_notice_hours'],
                'max_advance_days' => $validated['max_advance_days'],
                'timezone' => $tz,
            ],
        );

        return redirect()
            ->route('coach.booking.settings', $tenant)
            ->with('status', 'Booking settings saved.');
    }

    public function storeAvailability(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $start = (string) $request->input('start_time', '');
                    if ($start !== '' && strcmp((string) $value, $start) <= 0) {
                        $fail('End time must be after start time.');
                    }
                },
            ],
        ]);

        CoachWeeklyAvailability::query()->create([
            'tenant_id' => $tenant->id,
            'coach_user_id' => auth()->id(),
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
        ]);

        return redirect()
            ->route('coach.booking.settings', $tenant)
            ->with('status', 'Availability block added.');
    }

    public function destroyAvailability(Tenant $tenant, CoachWeeklyAvailability $availability): RedirectResponse
    {
        abort_unless($availability->tenant_id === $tenant->id && $availability->coach_user_id === auth()->id(), 404);
        $availability->delete();

        return redirect()
            ->route('coach.booking.settings', $tenant)
            ->with('status', 'Availability block removed.');
    }

    public function confirm(Tenant $tenant, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($tenant, $booking);
        $result = $this->respondToBooking->confirm($booking);

        if (! $result['ok']) {
            return back()->with('warning', $result['warning']);
        }

        return back()->with('status', 'Booking confirmed.');
    }

    public function decline(Request $request, Tenant $tenant, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($tenant, $booking);
        $validated = $request->validate([
            'coach_internal_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->respondToBooking->decline($booking, $validated['coach_internal_note'] ?? null);

        if (! $result['ok']) {
            return back()->with('warning', $result['warning']);
        }

        return back()->with('status', 'Booking declined.');
    }

    public function cancelCoach(Tenant $tenant, Booking $booking): RedirectResponse
    {
        $this->authorizeBooking($tenant, $booking);
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            return back()->with('warning', 'This booking cannot be cancelled.');
        }

        $booking->update([
            'status' => BookingStatus::CancelledByCoach,
            'responded_at' => now(),
        ]);

        return back()->with('status', 'Booking cancelled.');
    }

    private function authorizeBooking(Tenant $tenant, Booking $booking): void
    {
        abort_unless($booking->tenant_id === $tenant->id && $booking->coach_user_id === auth()->id(), 404);
    }
}
