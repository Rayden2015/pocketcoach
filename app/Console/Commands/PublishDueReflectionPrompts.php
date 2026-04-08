<?php

namespace App\Console\Commands;

use App\Models\ReflectionPrompt;
use Illuminate\Console\Command;

class PublishDueReflectionPrompts extends Command
{
    protected $signature = 'reflections:publish-due';

    protected $description = 'Mark scheduled reflection prompts as published when their scheduled time has passed';

    public function handle(): int
    {
        $due = ReflectionPrompt::query()
            ->where('is_published', false)
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now())
            ->get();

        foreach ($due as $prompt) {
            $when = $prompt->scheduled_publish_at;
            $prompt->forceFill([
                'is_published' => true,
                'published_at' => $when,
            ])->save();
        }

        if ($due->isNotEmpty()) {
            $this->info(sprintf('Published %d reflection(s).', $due->count()));
        }

        return self::SUCCESS;
    }
}
