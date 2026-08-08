<?php

namespace App\Jobs;

use App\Models\ReflectionPrompt;
use App\Notifications\ReflectionPromptPublishedNotification;
use App\Services\TenantLearnerAudienceService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

/**
 * Dispatched synchronously from the observer (does not implement ShouldQueue)
 * so the bus runs handle() immediately; individual {@see ReflectionPromptPublishedNotification}
 * messages remain queueable.
 */
class NotifyTenantLearnersOfReflectionPrompt
{
    use Queueable;

    public function __construct(
        public int $reflectionPromptId,
    ) {}

    public function handle(TenantLearnerAudienceService $audience): void
    {
        $prompt = ReflectionPrompt::query()
            ->whereKey($this->reflectionPromptId)
            ->with('tenant')
            ->first();

        if ($prompt === null || ! $prompt->is_published || $prompt->published_at === null) {
            return;
        }

        $audience->queryForTenant($prompt->tenant_id)
            ->chunkById(100, function ($users) use ($prompt): void {
                Notification::send($users, new ReflectionPromptPublishedNotification($prompt));
            });
    }
}
