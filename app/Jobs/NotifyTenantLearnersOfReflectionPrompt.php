<?php

namespace App\Jobs;

use App\Enums\TenantRole;
use App\Models\ReflectionPrompt;
use App\Models\User;
use App\Notifications\ReflectionPromptPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyTenantLearnersOfReflectionPrompt implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $reflectionPromptId,
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $prompt = ReflectionPrompt::query()
            ->whereKey($this->reflectionPromptId)
            ->with('tenant')
            ->first();

        if ($prompt === null || ! $prompt->is_published || $prompt->published_at === null) {
            return;
        }

        $tenantId = $prompt->tenant_id;

        User::query()
            ->whereHas('memberships', function ($q) use ($tenantId): void {
                $q->where('tenant_id', $tenantId)
                    ->where('role', TenantRole::Learner->value);
            })
            ->chunkById(100, function ($users) use ($prompt): void {
                Notification::send($users, new ReflectionPromptPublishedNotification($prompt));
            });
    }
}
