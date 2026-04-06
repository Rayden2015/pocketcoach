<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'branding',
        'settings',
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
