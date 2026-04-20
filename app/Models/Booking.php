<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'tenant_id',
        'coach_user_id',
        'booker_user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'starts_at',
        'ends_at',
        'status',
        'booker_message',
        'coach_internal_note',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'responded_at' => 'datetime',
            'status' => BookingStatus::class,
        ];
    }

    /**
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeReservingSlot(Builder $query): Builder
    {
        return $query->whereIn('status', [
            BookingStatus::Pending,
            BookingStatus::Confirmed,
        ]);
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function booker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booker_user_id');
    }

    public function bookerDisplayName(): string
    {
        if ($this->booker_user_id !== null && $this->booker) {
            return $this->booker->name ?: $this->booker->email;
        }

        return (string) $this->guest_name;
    }

    public function bookerContactEmail(): ?string
    {
        if ($this->booker_user_id !== null && $this->booker) {
            return $this->booker->email;
        }

        return $this->guest_email;
    }
}
