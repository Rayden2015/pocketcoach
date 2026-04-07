<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_PDF = 'pdf';

    public const TYPE_VIDEO = 'video';

    public const TYPE_AUDIO = 'audio';

    public const TYPE_IMAGE = 'image';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_PDF,
        self::TYPE_VIDEO,
        self::TYPE_AUDIO,
        self::TYPE_IMAGE,
    ];

    protected $fillable = [
        'tenant_id',
        'module_id',
        'title',
        'slug',
        'lesson_type',
        'body',
        'media_url',
        'media_disk_path',
        'meta',
        'sort_order',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Lesson $lesson): void {
            if ($lesson->media_disk_path) {
                Storage::disk('public')->delete($lesson->media_disk_path);
            }
        });
    }

    /**
     * Public URL for external links, or storage URL for coach uploads.
     */
    public function resolvedMediaUrl(): ?string
    {
        if ($this->media_url) {
            return $this->media_url;
        }
        if ($this->media_disk_path) {
            return Storage::disk('public')->url($this->media_disk_path);
        }

        return null;
    }

    /**
     * Convert a YouTube watch / short URL to an embed URL, if applicable.
     */
    public static function youtubeEmbedUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('/youtube\.com\/watch\?([^#]*&)?v=([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[2];
        }
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        return null;
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * @return HasMany<LessonProgress, $this>
     */
    public function progressRecords(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
