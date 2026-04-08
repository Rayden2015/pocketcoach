<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReflectionPromptView extends Model
{
    protected $fillable = [
        'reflection_prompt_id',
        'user_id',
        'first_viewed_at',
        'last_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
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
}
