<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'slug',
        'status',
        'branding',
        'settings',
        'custom_domain',
    ];

    protected function casts(): array
    {
        return [
            'branding' => 'array',
            'settings' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Absolute URL for this space on the app host (path-based tenancy).
     * Custom domains can be layered later via custom_domain + DNS.
     */
    public function publicUrl(string $path = ''): string
    {
        $base = rtrim(config('app.url'), '/');
        $p = ltrim($path, '/');

        return $p === '' ? "{$base}/{$this->slug}" : "{$base}/{$this->slug}/{$p}";
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return BelongsToMany<User, $this, TenantMembership>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_memberships', 'tenant_id', 'user_id')
            ->using(TenantMembership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<TenantMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /**
     * @return HasMany<Program, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
