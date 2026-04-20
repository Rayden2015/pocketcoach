<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class NewBookingRequestNotification extends Notification implements ShouldQueue
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
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $tenant = $this->booking->tenant;
        $url = $tenant->publicUrl('coach/bookings');

        return [
            'title' => 'New booking request',
            'body' => $this->booking->bookerDisplayName().' requested '.optional($this->booking->starts_at)->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            'kind' => 'booking_request',
            'booking_id' => $this->booking->id,
            'tenant_slug' => $tenant->slug,
            'url' => $url,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->booking->tenant;
        $ttlDays = max(1, (int) config('booking.mail_response_link_ttl_days', 7));
        $expires = Carbon::now()->addDays($ttlDays);

        $confirmUrl = URL::temporarySignedRoute(
            'mail.booking.confirm',
            $expires,
            ['tenant' => $tenant, 'booking' => $this->booking],
        );

        $declineUrl = URL::temporarySignedRoute(
            'mail.booking.decline',
            $expires,
            ['tenant' => $tenant, 'booking' => $this->booking],
        );

        $consoleUrl = $tenant->publicUrl('coach/bookings');
        $when = optional($this->booking->starts_at)->timezone(config('app.timezone'))->toDayDateTimeString();
        $booker = $this->booking->bookerDisplayName();
        $messagePreview = $this->booking->booker_message
            ? str(strip_tags((string) $this->booking->booker_message))->limit(400)->toString()
            : null;

        $name = trim((string) ($notifiable->name ?? ''));
        $greeting = $name !== '' ? 'Hi '.$name.',' : 'Hello,';

        $cta = new HtmlString(
            '<p style="margin:20px 0 8px;">'
            .'<a href="'.e($confirmUrl).'" style="display:inline-block;padding:12px 22px;background:#0d9488;color:#ffffff;text-decoration:none;border-radius:9999px;font-weight:600;">Confirm</a>'
            .'<span style="display:inline-block;width:12px;"></span>'
            .'<a href="'.e($declineUrl).'" style="font-weight:600;color:#b91c1c;">Decline</a>'
            .'</p>'
            .'<p style="margin:0 0 16px;font-size:12px;color:#64748b;">These links expire in '.$ttlDays.' day(s). Anyone with the link can respond — keep this email private.</p>'
        );

        $mail = (new MailMessage)
            ->subject('New booking request — '.$tenant->name)
            ->greeting($greeting)
            ->line($booker.' requested a coaching session in **'.$tenant->name.'**.')
            ->line('**When:** '.$when);

        if ($messagePreview !== null) {
            $mail->line('**Their message:** '.$messagePreview);
        }

        $mail->line($cta)
            ->action('Open coach bookings', $consoleUrl)
            ->line('You can also confirm or decline from the app after you sign in.');

        $reply = $this->booking->bookerContactEmail();
        if ($reply !== null && $reply !== '') {
            $mail->replyTo($reply, $booker);
        }

        return $mail;
    }
}
