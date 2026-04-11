<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use App\Models\ReflectionResponse;
use App\Models\SubmissionConversationMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubmissionConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SubmissionConversationController extends Controller
{
    public function __construct(
        private SubmissionConversationService $conversations,
    ) {}

    public function indexReflection(Request $request, Tenant $tenant, ReflectionResponse $reflectionResponse): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->conversations->canView($user, $tenant, $reflectionResponse), 403);

        return response()->json([
            'data' => $this->serializeMessages($this->conversations->messagesFor($reflectionResponse)),
        ]);
    }

    public function storeReflection(Request $request, Tenant $tenant, ReflectionResponse $reflectionResponse): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->conversations->canPost($user, $tenant, $reflectionResponse), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:65535'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $message = $this->conversations->createMessage(
            $tenant,
            $reflectionResponse,
            $user,
            $validated['body'],
            $validated['parent_id'] ?? null,
        );

        return response()->json([
            'data' => $this->serializeMessage($message),
        ], 201);
    }

    public function indexLesson(Request $request, Tenant $tenant, LessonProgress $lessonProgress): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($lessonProgress->tenant_id === $tenant->id, 404);
        abort_unless($this->conversations->canView($user, $tenant, $lessonProgress), 403);

        return response()->json([
            'data' => $this->serializeMessages($this->conversations->messagesFor($lessonProgress)),
        ]);
    }

    public function storeLesson(Request $request, Tenant $tenant, LessonProgress $lessonProgress): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($lessonProgress->tenant_id === $tenant->id, 404);
        abort_unless($this->conversations->canPost($user, $tenant, $lessonProgress), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:65535'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $message = $this->conversations->createMessage(
            $tenant,
            $lessonProgress,
            $user,
            $validated['body'],
            $validated['parent_id'] ?? null,
        );

        return response()->json([
            'data' => $this->serializeMessage($message),
        ], 201);
    }

    /**
     * @param  Collection<int, SubmissionConversationMessage>  $messages
     * @return list<array<string, mixed>>
     */
    private function serializeMessages($messages): array
    {
        return $messages->map(fn ($m) => $this->serializeMessage($m))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(SubmissionConversationMessage $m): array
    {
        return [
            'id' => $m->id,
            'body' => $m->body,
            'parent_id' => $m->parent_id,
            'user' => [
                'id' => $m->user_id,
                'name' => $m->user?->name,
            ],
            'created_at' => $m->created_at?->toIso8601String(),
            'updated_at' => $m->updated_at?->toIso8601String(),
        ];
    }
}
