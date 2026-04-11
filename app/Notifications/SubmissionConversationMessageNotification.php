<?php

namespace App\Notifications;

use App\Models\LessonProgress;
use App\Models\ReflectionResponse;
use App\Models\SubmissionConversationMessage;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionConversationMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public SubmissionConversationMessage $message,
        public ReflectionResponse|LessonProgress $subject,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $kind = $this->subject instanceof ReflectionResponse ? 'reflection' : 'lesson';

        $url = $this->subject instanceof ReflectionResponse
            ? route('submission-conversations.reflection.show', [$this->tenant, $this->subject], absolute: true)
            : route('submission-conversations.lesson.show', [$this->tenant, $this->subject], absolute: true);

        $author = $this->message->user?->name ?? 'Someone';

        return [
            'tenant_id' => $this->tenant->id,
            'tenant_slug' => $this->tenant->slug,
            'kind' => $kind,
            'message_id' => $this->message->id,
            'author_id' => $this->message->user_id,
            'author_name' => $this->message->user?->name,
            'body_preview' => str($this->message->body)->limit(200)->toString(),
            'reflection_response_id' => $this->subject instanceof ReflectionResponse ? $this->subject->id : null,
            'lesson_progress_id' => $this->subject instanceof LessonProgress ? $this->subject->id : null,
            'title' => "Message from {$author}",
            'url' => $url,
        ];
    }
}
