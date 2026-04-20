<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachBookingSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'coach_user_id',
        'enabled',
        'slot_duration_minutes',
        'buffer_minutes',
        'min_notice_hours',
        'max_advance_days',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }

    public function effectiveTimezone(): string
    {
        if (filled($this->timezone)) {
            return $this->timezone;
        }

        $tz = $this->coach?->timezone;

        return filled($tz) ? $tz : (string) config('app.timezone');
    }
}
