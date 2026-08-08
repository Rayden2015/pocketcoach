<?php

namespace App\Listeners;

use App\Support\NotificationLog;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

class LogNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        Log::info('notification.sent', [
            'notification' => class_basename($event->notification),
            'channel' => $event->channel,
            'notifiable' => NotificationLog::notifiableSummary($event->notifiable),
        ]);
    }
}
