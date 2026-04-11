<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($n) {
                $data = $n->data ?? [];

                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'data' => $data,
                    'title' => $this->notificationTitle($data, (string) $n->type),
                    'preview' => $this->notificationPreview($data),
                    'url' => $this->resolveNotificationUrl($data),
                    'read_at' => $n->read_at?->toIso8601String(),
                    'created_at' => $n->created_at?->toIso8601String(),
                ];
            });

        return response()->json(['data' => $notifications]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notificationTitle(array $data, string $typeClass): string
    {
        if (! empty($data['title']) && is_string($data['title'])) {
            return $data['title'];
        }

        if (str_contains($typeClass, 'SubmissionConversationMessage')) {
            $who = isset($data['author_name']) && is_string($data['author_name']) ? $data['author_name'] : 'Someone';

            return "Message from {$who}";
        }

        return 'Notification';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notificationPreview(array $data): ?string
    {
        if (! empty($data['body_preview']) && is_string($data['body_preview'])) {
            return $data['body_preview'];
        }
        if (! empty($data['body']) && is_string($data['body'])) {
            return $data['body'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveNotificationUrl(array $data): ?string
    {
        if (! empty($data['url']) && is_string($data['url'])) {
            return $data['url'];
        }

        $slug = isset($data['tenant_slug']) && is_string($data['tenant_slug']) ? $data['tenant_slug'] : null;
        if ($slug === null || $slug === '') {
            return null;
        }

        $kind = $data['kind'] ?? null;
        if ($kind === 'reflection' && ! empty($data['reflection_response_id'])) {
            return url("/{$slug}/submission-conversations/reflection/".$data['reflection_response_id']);
        }
        if ($kind === 'lesson' && ! empty($data['lesson_progress_id'])) {
            return url("/{$slug}/submission-conversations/lesson/".$data['lesson_progress_id']);
        }

        return null;
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
            $notification->refresh();
        }

        return response()->json([
            'data' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at?->toIso8601String(),
            ],
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $marked = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'marked' => $marked,
        ]);
    }
}
