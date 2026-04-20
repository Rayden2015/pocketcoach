<?php

namespace App\Services\Booking;

use App\Enums\TenantRole;
use App\Models\Booking;
use App\Models\CoachBookingSetting;
use App\Models\CoachWeeklyAvailability;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

final class BookingSlotService
{
    /**
     * Staff members in this tenant who enabled public booking.
     *
     * @return Collection<int, User>
     */
    public function bookableCoaches(Tenant $tenant): Collection
    {
        return User::query()
            ->whereHas('memberships', function ($q) use ($tenant): void {
                $q->where('tenant_id', $tenant->id)
                    ->whereIn('role', TenantRole::staffValues());
            })
            ->whereHas('coachBookingSettings', function ($q) use ($tenant): void {
                $q->where('tenant_id', $tenant->id)->where('enabled', true);
            })
            ->orderBy('name')
            ->orderBy('email')
            ->get();
    }

    /**
     * @return list<array{start: string, end: string, start_local: string, end_local: string}>
     */
    public function availableSlotsUtc(Tenant $tenant, int $coachUserId, ?string $fromDate, ?string $toDate): array
    {
        $settings = CoachBookingSetting::query()
            ->where('tenant_id', $tenant->id)
            ->where('coach_user_id', $coachUserId)
            ->where('enabled', true)
            ->first();

        if ($settings === null) {
            return [];
        }

        $tz = $settings->effectiveTimezone();
        $nowTz = Carbon::now($tz);
        $minStart = $nowTz->copy()->addHours($settings->min_notice_hours);

        $from = $fromDate !== null && $fromDate !== ''
            ? Carbon::parse($fromDate, $tz)->startOfDay()
            : $nowTz->copy()->startOfDay();
        if ($from->lt($nowTz->copy()->startOfDay())) {
            $from = $nowTz->copy()->startOfDay();
        }

        $to = $toDate !== null && $toDate !== ''
            ? Carbon::parse($toDate, $tz)->endOfDay()
            : $from->copy()->addDays(13)->endOfDay();

        $maxUntil = $nowTz->copy()->addDays($settings->max_advance_days)->endOfDay();
        if ($to->gt($maxUntil)) {
            $to = $maxUntil;
        }

        if ($from->gt($to)) {
            return [];
        }

        $availabilities = CoachWeeklyAvailability::query()
            ->where('tenant_id', $tenant->id)
            ->where('coach_user_id', $coachUserId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        if ($availabilities->isEmpty()) {
            return [];
        }

        $blocking = Booking::query()
            ->where('coach_user_id', $coachUserId)
            ->reservingSlot()
            ->orderBy('starts_at')
            ->get(['starts_at', 'ends_at']);

        $duration = max(5, (int) $settings->slot_duration_minutes);
        $buffer = max(0, (int) $settings->buffer_minutes);

        $slots = [];

        foreach (CarbonPeriod::create($from->toDateString(), $to->toDateString()) as $date) {
            /** @var Carbon $date */
            $dow = (int) $date->format('w');
            $dayRules = $availabilities->where('day_of_week', $dow);
            foreach ($dayRules as $rule) {
                $startStr = $this->normalizeTimeString($rule->start_time);
                $endStr = $this->normalizeTimeString($rule->end_time);
                $windowStart = Carbon::parse($date->toDateString().' '.$startStr, $tz);
                $windowEnd = Carbon::parse($date->toDateString().' '.$endStr, $tz);
                if ($windowEnd->lte($windowStart)) {
                    continue;
                }

                $cursor = $windowStart->copy();
                while (true) {
                    $slotEnd = $cursor->copy()->addMinutes($duration);
                    if ($slotEnd->gt($windowEnd)) {
                        break;
                    }
                    if ($cursor->lt($minStart)) {
                        $cursor->addMinutes($duration + $buffer);

                        continue;
                    }
                    $startUtc = $cursor->copy()->utc();
                    $endUtc = $slotEnd->copy()->utc();
                    if (! $this->overlapsBlocking($blocking, $startUtc, $endUtc)) {
                        $slots[] = [
                            'start' => $startUtc->toIso8601String(),
                            'end' => $endUtc->toIso8601String(),
                            'start_local' => $cursor->toIso8601String(),
                            'end_local' => $slotEnd->toIso8601String(),
                        ];
                    }
                    $cursor->addMinutes($duration + $buffer);
                }
            }
        }

        return $slots;
    }

    public function overlapsExistingBooking(int $coachUserId, Carbon $startsUtc, Carbon $endsUtc, ?int $ignoreBookingId = null): bool
    {
        return Booking::query()
            ->where('coach_user_id', $coachUserId)
            ->reservingSlot()
            ->when($ignoreBookingId !== null, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->where('starts_at', '<', $endsUtc)
            ->where('ends_at', '>', $startsUtc)
            ->exists();
    }

    /**
     * @param  Collection<int, Booking>  $blocking
     */
    private function overlapsBlocking(Collection $blocking, Carbon $startUtc, Carbon $endUtc): bool
    {
        foreach ($blocking as $b) {
            if ($b->starts_at->lt($endUtc) && $b->ends_at->gt($startUtc)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeTimeString(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i:s');
        }
        $s = (string) $value;
        if (strlen($s) === 5) {
            return $s.':00';
        }

        return $s;
    }
}
