<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SpaceInvite extends Model
{
    protected $fillable = [
        'tenant_id',
        'email',
        'role',
        'invited_by_user_id',
        'token',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isPast();
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
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function acceptUrl(): string
    {
        return route('space.coach-invite.show', [
            'tenant' => $this->tenant,
            'token' => $this->token,
        ]);
    }
}
