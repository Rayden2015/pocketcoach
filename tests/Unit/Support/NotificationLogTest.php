<?php

namespace Tests\Unit\Support;

use App\Listeners\LogNotificationFailed;
use App\Support\NotificationLog;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class NotificationLogTest extends TestCase
{
    public function test_anonymous_notifiable_summary_includes_mail_route(): void
    {
        $notifiable = (new AnonymousNotifiable)->route('mail', 'coach@example.com');

        $summary = NotificationLog::notifiableSummary($notifiable);

        $this->assertSame('anonymous', $summary['type']);
        $this->assertSame(['mail' => 'coach@example.com'], $summary['routes']);
    }

    public function test_notification_failed_listener_handles_anonymous_notifiable(): void
    {
        $notification = new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['mail'];
            }
        };

        $event = new NotificationFailed(
            (new AnonymousNotifiable)->route('mail', 'coach@example.com'),
            $notification,
            'mail',
            ['message' => 'SMTP connection refused'],
        );

        (new LogNotificationFailed)->handle($event);

        $this->assertTrue(true);
    }
}
