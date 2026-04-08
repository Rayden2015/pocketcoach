<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReflectionPrompt extends Model
{
    protected $fillable = [
        'tenant_id',
        'author_id',
        'title',
        'body',
        'is_published',
        'published_at',
        'scheduled_publish_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_publish_at' => 'datetime',
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return HasMany<ReflectionPromptView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(ReflectionPromptView::class);
    }

    /**
     * @return HasMany<ReflectionResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ReflectionResponse::class);
    }
}
