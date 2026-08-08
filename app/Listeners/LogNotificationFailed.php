<?php

namespace App\Listeners;

use App\Support\NotificationLog;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Log;

class LogNotificationFailed
{
    public function handle(NotificationFailed $event): void
    {
        $notification = class_basename($event->notification);

        Log::warning('notification.failed', [
            'notification' => $notification,
            'channel' => $event->channel,
            'notifiable' => NotificationLog::notifiableSummary($event->notifiable),
            'data' => $this->safeData($event->data),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function safeData(array $data): array
    {
        unset($data['token'], $data['password']);

        return $data;
    }
}
