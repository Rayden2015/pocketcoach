<?php

namespace App\Listeners;

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
            'notifiable_type' => $event->notifiable::class,
            'notifiable_id' => $event->notifiable->getKey(),
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
