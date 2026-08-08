<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingDeclinedNotification extends Notification implements ShouldQueue
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
            'title' => 'Booking declined',
            'body' => 'Your session request for '.$when.' was declined.',
            'kind' => 'booking_declined',
            'booking_id' => $this->booking->id,
            'tenant_slug' => $tenant->slug,
            'url' => $tenant->publicUrl('book'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->booking->tenant;
        $when = optional($this->booking->starts_at)->timezone(config('app.timezone'))->toDayDateTimeString();

        return (new MailMessage)
            ->subject('Booking declined — '.$tenant->name)
            ->line('Your coaching session request in **'.$tenant->name.'** was declined.')
            ->line('**Requested time:** '.$when)
            ->action('Pick another time', $tenant->publicUrl('book'))
            ->line('You can choose another open slot when you are ready.');
    }
}
