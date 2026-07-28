<?php

namespace App\Notifications;

use App\Models\ReflectionPrompt;
use App\Services\TenantEngagementSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ReflectionPromptPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ReflectionPrompt $prompt,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $cfg = TenantEngagementSettings::reflections($this->prompt->tenant);
        if (! $cfg['enabled']) {
            return [];
        }

        $channels = [];
        if ($cfg['notify_database']) {
            $channels[] = 'database';
        }
        if ($cfg['notify_email']) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $tenant = $this->prompt->tenant;
        $url = $tenant->publicUrl('learn/reflections/'.$this->prompt->id);

        return [
            'title' => $this->prompt->title ?? 'New reflection prompt',
            'body' => str(strip_tags($this->prompt->body))->limit(200)->toString(),
            'kind' => 'reflection_prompt',
            'reflection_prompt_id' => $this->prompt->id,
            'tenant_slug' => $tenant->slug,
            'url' => $url,
            'image_url' => $this->prompt->resolvedImageUrl(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->prompt->tenant;
        $url = $tenant->publicUrl('learn/reflections/'.$this->prompt->id);
        $subject = ($this->prompt->title ?: 'Daily reflection').' — '.$tenant->name;

        $mail = (new MailMessage)
            ->subject($subject)
            ->line('Your coach posted a new reflection in '.$tenant->name.'.')
            ->line(str(strip_tags($this->prompt->body))->limit(500)->toString());

        if ($imageUrl = $this->prompt->resolvedImageUrl()) {
            $mail->line(new HtmlString(
                '<p style="margin:16px 0;"><img src="'.e($imageUrl).'" alt="Reflection image" style="max-width:100%;height:auto;border-radius:8px;"></p>'
            ));
        }

        return $mail
            ->action('Open reflection', $url)
            ->line('Reply in the app when you are ready.');
    }
}
