<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequestReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof \App\Models\User
            ? ['database', 'mail']
            : ['mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $tenant = $this->booking->tenant;
        $when = optional($this->booking->starts_at)->timezone(config('app.timezone'))->format('M j, Y g:i A');

        return [
            'title' => 'Booking request received',
            'body' => 'Your session request for '.$when.' was sent to the coach.',
            'kind' => 'booking_received',
            'booking_id' => $this->booking->id,
            'tenant_slug' => $tenant->slug,
            'url' => $tenant->publicUrl('book'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->booking->tenant;
        $when = optional($this->booking->starts_at)->timezone(config('app.timezone'))->toDayDateTimeString();
        $coachName = $this->booking->coach?->name ?: 'your coach';

        return (new MailMessage)
            ->subject('Booking request received — '.$tenant->name)
            ->line('We received your coaching session request in **'.$tenant->name.'**.')
            ->line('**When:** '.$when)
            ->line('**Coach:** '.$coachName)
            ->line('The coach will confirm or decline by email. You will receive another message when they respond.');
    }
}
