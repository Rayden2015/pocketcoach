<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CoachBookingSetting;
use App\Models\CoachWeeklyAvailability;
use App\Models\Tenant;
use App\Services\Booking\BookingSlotService;
use App\Support\BookingTimezoneCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoachBookingController extends Controller
{
    public function __construct(
        private BookingSlotService $slots,
    ) {}

    public function index(Tenant $tenant): JsonResponse
    {
        $bookings = Booking::query()
            ->where('tenant_id', $tenant->id)
            ->where('coach_user_id', auth()->id())
            ->with(['booker'])
            ->orderByDesc('starts_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $bookings->map(fn (Booking $b) => $this->bookingPayload($b))->values()->all(),
        ]);
    }

    public function settings(Tenant $tenant): JsonResponse
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

        return response()->json([
            'data' => [
                'settings' => [
                    'enabled' => $settings->enabled,
                    'slot_duration_minutes' => $settings->slot_duration_minutes,
                    'buffer_minutes' => $settings->buffer_minutes,
                    'min_notice_hours' => $settings->min_notice_hours,
                    'max_advance_days' => $settings->max_advance_days,
                    'timezone' => $settings->timezone,
                    'timezone_options' => BookingTimezoneCatalog::selectOptions(),
                ],
                'weekly_availability' => $availability->map(fn (CoachWeeklyAvailability $a) => [
                    'id' => $a->id,
                    'day_of_week' => $a->day_of_week,
                    'start_time' => $this->formatTime($a->start_time),
                    'end_time' => $this->formatTime($a->end_time),
                ])->values()->all(),
            ],
        ]);
    }

    public function updateSettings(Request $request, Tenant $tenant): JsonResponse
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

        return response()->json(['message' => 'Booking settings saved.']);
    }

    public function storeAvailability(Request $request, Tenant $tenant): JsonResponse
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

        $row = CoachWeeklyAvailability::query()->create([
            'tenant_id' => $tenant->id,
            'coach_user_id' => auth()->id(),
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
        ]);

        return response()->json([
            'data' => [
                'id' => $row->id,
                'day_of_week' => $row->day_of_week,
                'start_time' => $this->formatTime($row->start_time),
                'end_time' => $this->formatTime($row->end_time),
            ],
        ], 201);
    }

    public function destroyAvailability(Tenant $tenant, CoachWeeklyAvailability $availability): JsonResponse
    {
        abort_unless($availability->tenant_id === $tenant->id && $availability->coach_user_id === auth()->id(), 404);
        $availability->delete();

        return response()->json(['message' => 'Availability block removed.']);
    }

    public function confirm(Tenant $tenant, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($tenant, $booking);
        if ($booking->status !== BookingStatus::Pending) {
            return response()->json(['message' => 'Only pending requests can be confirmed.'], 422);
        }

        if ($this->slots->overlapsExistingBooking($booking->coach_user_id, $booking->starts_at, $booking->ends_at, $booking->id)) {
            return response()->json(['message' => 'Another booking already holds this slot.'], 422);
        }

        $booking->update([
            'status' => BookingStatus::Confirmed,
            'responded_at' => now(),
        ]);

        return response()->json(['data' => $this->bookingPayload($booking->fresh(['booker']))]);
    }

    public function decline(Request $request, Tenant $tenant, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($tenant, $booking);
        if ($booking->status !== BookingStatus::Pending) {
            return response()->json(['message' => 'Only pending requests can be declined.'], 422);
        }

        $validated = $request->validate([
            'coach_internal_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $booking->update([
            'status' => BookingStatus::Declined,
            'coach_internal_note' => $validated['coach_internal_note'] ?? null,
            'responded_at' => now(),
        ]);

        return response()->json(['data' => $this->bookingPayload($booking->fresh(['booker']))]);
    }

    public function cancel(Tenant $tenant, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($tenant, $booking);
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            return response()->json(['message' => 'This booking cannot be cancelled.'], 422);
        }

        $booking->update([
            'status' => BookingStatus::CancelledByCoach,
            'responded_at' => now(),
        ]);

        return response()->json(['data' => $this->bookingPayload($booking->fresh(['booker']))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(Booking $b): array
    {
        return [
            'id' => $b->id,
            'status' => $b->status->value,
            'starts_at' => $b->starts_at->toIso8601String(),
            'ends_at' => $b->ends_at->toIso8601String(),
            'booker_message' => $b->booker_message,
            'coach_internal_note' => $b->coach_internal_note,
            'booker_display_name' => $b->bookerDisplayName(),
            'booker_email' => $b->bookerContactEmail(),
            'guest_phone' => $b->guest_phone,
            'booker_user_id' => $b->booker_user_id,
        ];
    }

    private function formatTime(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        $s = (string) $value;

        return strlen($s) >= 5 ? substr($s, 0, 5) : $s;
    }

    private function authorizeBooking(Tenant $tenant, Booking $booking): void
    {
        abort_unless($booking->tenant_id === $tenant->id && $booking->coach_user_id === auth()->id(), 404);
    }
}
