<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class LogQueueJobFailed
{
    public function handle(JobFailed $event): void
    {
        Log::error('queue.job_failed', [
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'job' => $event->job->resolveName(),
            'exception' => $event->exception->getMessage(),
        ]);
    }
}
