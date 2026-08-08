<?php

namespace App\Notifications;

use App\Models\SpaceAnnouncement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SpaceAnnouncementPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SpaceAnnouncement $announcement,
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
        $tenant = $this->announcement->tenant;
        $url = $tenant->publicUrl('learn/announcements/'.$this->announcement->id);

        return [
            'title' => $this->announcement->title,
            'body' => str(strip_tags($this->announcement->body))->limit(200)->toString(),
            'kind' => 'space_announcement',
            'announcement_id' => $this->announcement->id,
            'tenant_slug' => $tenant->slug,
            'url' => $url,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->announcement->tenant;
        $url = $tenant->publicUrl('learn/announcements/'.$this->announcement->id);

        return (new MailMessage)
            ->subject($this->announcement->title.' — '.$tenant->name)
            ->line('**'.$tenant->name.'** posted an announcement:')
            ->line(str(strip_tags($this->announcement->body))->limit(500)->toString())
            ->action('Read announcement', $url);
    }
}
