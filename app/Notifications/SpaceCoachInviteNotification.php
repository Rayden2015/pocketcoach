<?php

namespace App\Notifications;

use App\Models\SpaceInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SpaceCoachInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SpaceInvite $invite,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->invite->tenant;
        $inviter = $this->invite->invitedBy;
        $roleLabel = $this->invite->role === 'admin' ? 'admin' : 'instructor';
        $url = $this->invite->acceptUrl();

        return (new MailMessage)
            ->subject('You are invited to coach in '.$tenant->name)
            ->greeting('Hello!')
            ->line(($inviter?->name ?? 'A coach').' invited you to join '.$tenant->name.' as an '.$roleLabel.'.')
            ->line('Accept the invite to set your password (or sign in if you already have an account) and open the coach workspace.')
            ->action('Accept invitation', $url)
            ->line('This link expires on '.$this->invite->expires_at->timezone(config('app.timezone'))->format('M j, Y g:i A').'.');
    }
}
