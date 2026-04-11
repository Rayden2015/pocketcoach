<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReflectionResponse extends Model
{
    protected $fillable = [
        'reflection_prompt_id',
        'user_id',
        'body',
        'is_public',
        'first_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'first_submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ReflectionPrompt, $this>
     */
    public function reflectionPrompt(): BelongsTo
    {
        return $this->belongsTo(ReflectionPrompt::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Coach ↔ learner thread anchored on this submission (excludes the stored reflection body).
     *
     * @return MorphMany<SubmissionConversationMessage, $this>
     */
    public function conversationMessages(): MorphMany
    {
        return $this->morphMany(SubmissionConversationMessage::class, 'subject')->orderBy('created_at');
    }
}
