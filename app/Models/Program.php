<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Program extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'summary',
        'image_disk_path',
        'sort_order',
        'is_published',
        'is_featured',
        'catalog_view_count',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Program $program): void {
            if ($program->image_disk_path) {
                Storage::disk('public')->delete($program->image_disk_path);
            }
        });
    }

    public function resolvedImageUrl(): ?string
    {
        if ($this->image_disk_path) {
            return Storage::disk('public')->url($this->image_disk_path);
        }

        return null;
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
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
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
