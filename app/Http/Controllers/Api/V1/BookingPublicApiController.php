<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Booking\SubmitBookingRequestAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Notifications\NewBookingRequestNotification;
use App\Services\Booking\BookingSlotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingPublicApiController extends Controller
{
    public function __construct(
        private BookingSlotService $slots,
        private SubmitBookingRequestAction $submitBooking,
    ) {}

    public function coaches(Tenant $tenant): JsonResponse
    {
        if (! $tenant->isActive()) {
            return response()->json(['message' => 'Space not available.'], 404);
        }

        $coaches = $this->slots->bookableCoaches($tenant);

        return response()->json([
            'data' => $coaches->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ])->values()->all(),
        ]);
    }

    public function slots(Request $request, Tenant $tenant): JsonResponse
    {
        if (! $tenant->isActive()) {
            return response()->json(['message' => 'Space not available.'], 404);
        }

        $validated = $request->validate([
            'coach_user_id' => ['required', 'integer'],
            'from' => ['nullable', 'string'],
            'to' => ['nullable', 'string'],
        ]);

        $coachId = (int) $validated['coach_user_id'];
        $coaches = $this->slots->bookableCoaches($tenant);
        if (! $coaches->contains('id', $coachId)) {
            return response()->json(['message' => 'Coach not available for booking.'], 422);
        }

        $slots = $this->slots->availableSlotsUtc(
            $tenant,
            $coachId,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        return response()->json(['data' => $slots]);
    }

    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        if (! $tenant->isActive()) {
            return response()->json(['message' => 'Space not available.'], 404);
        }

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

        $startsUtc = Carbon::parse($validated['starts_at'])->utc();
        $endsUtc = Carbon::parse($validated['ends_at'])->utc();

        $result = $this->submitBooking->handle(
            $tenant,
            (int) $validated['coach_user_id'],
            $startsUtc,
            $endsUtc,
            $request->user(),
            $validated['guest_name'] ?? null,
            $validated['guest_email'] ?? null,
            $validated['guest_phone'] ?? null,
            $validated['booker_message'] ?? null,
        );

        if (! $result['ok']) {
            $message = match ($result['code']) {
                'coach' => 'This coach is not accepting bookings.',
                'slot' => 'That time slot is no longer available.',
                'taken' => 'That time slot was just taken. Pick another.',
            };
            $field = match ($result['code']) {
                'coach' => 'coach_user_id',
                default => 'starts_at',
            };

            return response()->json([
                'message' => $message,
                'errors' => [$field => [$message]],
            ], 422);
        }

        $booking = $result['booking']->load('coach');
        if ($booking->coach) {
            $booking->coach->notify(new NewBookingRequestNotification($booking));
        }

        return response()->json([
            'data' => [
                'id' => $booking->id,
                'status' => $booking->status->value,
                'starts_at' => $booking->starts_at->toIso8601String(),
                'ends_at' => $booking->ends_at->toIso8601String(),
            ],
        ], 201);
    }
}
