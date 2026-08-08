<?php

namespace App\Support;

use Illuminate\Notifications\AnonymousNotifiable;

final class NotificationLog
{
    /**
     * @return array<string, mixed>
     */
    public static function notifiableSummary(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return [
                'type' => 'anonymous',
                'routes' => $notifiable->routes ?? [],
            ];
        }

        return [
            'type' => class_basename($notifiable),
            'id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
        ];
    }
}
