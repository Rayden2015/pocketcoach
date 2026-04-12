<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'lesson_id',
        'completed_at',
        'notes',
        'notes_is_public',
        'position_seconds',
        'content_progress_percent',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'notes_is_public' => 'boolean',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return MorphMany<SubmissionConversationMessage, $this>
     */
    public function conversationMessages(): MorphMany
    {
        return $this->morphMany(SubmissionConversationMessage::class, 'subject')->orderBy('created_at');
    }
}
