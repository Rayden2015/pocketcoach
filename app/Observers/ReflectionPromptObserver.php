<?php

namespace App\Observers;

use App\Jobs\NotifyTenantLearnersOfReflectionPrompt;
use App\Models\ReflectionPrompt;

class ReflectionPromptObserver
{
    public function saved(ReflectionPrompt $prompt): void
    {
        if (! $prompt->is_published || $prompt->published_at === null) {
            return;
        }

        if ($prompt->wasRecentlyCreated) {
            NotifyTenantLearnersOfReflectionPrompt::dispatch($prompt->id);

            return;
        }

        if ($prompt->wasChanged('is_published') && $prompt->is_published) {
            NotifyTenantLearnersOfReflectionPrompt::dispatch($prompt->id);
        }
    }
}
