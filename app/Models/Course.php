<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    protected $fillable = [
        'tenant_id',
        'program_id',
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
        static::deleting(function (Course $course): void {
            if ($course->image_disk_path) {
                Storage::disk('public')->delete($course->image_disk_path);
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
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Lessons attached directly to the course (no module). Shown before modules in the learner UI.
     *
     * @return HasMany<Lesson, $this>
     */
    public function rootLessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->whereNull('module_id');
    }

    /**
     * @return HasMany<Module, $this>
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    /**
     * @return HasMany<CourseReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class);
    }

    /**
     * @return array{average_rating: ?float, reviews_count: int}
     */
    public function reviewSummaryFromAttributes(): array
    {
        $count = (int) ($this->reviews_count ?? 0);
        $avg = $this->reviews_avg_rating;

        return [
            'average_rating' => $count > 0 ? round((float) $avg, 1) : null,
            'reviews_count' => $count,
        ];
    }
}
