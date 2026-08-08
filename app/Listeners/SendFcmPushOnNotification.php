<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\FcmPushService;
use Illuminate\Notifications\Events\NotificationSent;

class SendFcmPushOnNotification
{
    public function __construct(
        private FcmPushService $fcm,
    ) {}

    public function handle(NotificationSent $event): void
    {
        if (! $this->fcm->isConfigured()) {
            return;
        }

        if ($event->channel !== 'database') {
            return;
        }

        if (! $event->notifiable instanceof User) {
            return;
        }

        if (! method_exists($event->notification, 'toArray')) {
            return;
        }

        $payload = $event->notification->toArray($event->notifiable);
        $title = (string) ($payload['title'] ?? 'Pocket Coach');
        $body = (string) ($payload['body'] ?? '');

        if ($body === '') {
            return;
        }

        $data = [];
        foreach (['kind', 'tenant_slug', 'url'] as $key) {
            if (! empty($payload[$key]) && is_string($payload[$key])) {
                $data[$key] = $payload[$key];
            }
        }

        $this->fcm->sendToUser($event->notifiable->id, [
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}
