<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use App\Models\ReflectionResponse;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SubmissionConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SubmissionConversationController extends Controller
{
    public function __construct(
        private SubmissionConversationService $conversations,
    ) {}

    public function showReflection(Request $request, Tenant $tenant, ReflectionResponse $reflectionResponse): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->conversations->canView($user, $tenant, $reflectionResponse), 403);

        $reflectionResponse->load(['reflectionPrompt', 'user:id,name,email']);

        $messages = $this->conversations->messagesFor($reflectionResponse);
        $byId = $messages->keyBy('id');

        return view('submission-conversation.reflection', [
            'tenant' => $tenant,
            'subject' => $reflectionResponse,
            'prompt' => $reflectionResponse->reflectionPrompt,
            'learner' => $reflectionResponse->user,
            'messages' => $messages,
            'messageById' => $byId,
            'messageDepth' => $this->messageDepthMap($messages, $byId),
            'isStaff' => $this->conversations->isStaff($user, $tenant),
        ]);
    }

    public function storeReflection(Request $request, Tenant $tenant, ReflectionResponse $reflectionResponse): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->conversations->canPost($user, $tenant, $reflectionResponse), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:65535'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->conversations->createMessage(
            $tenant,
            $reflectionResponse,
            $user,
            $validated['body'],
            $validated['parent_id'] ?? null,
        );

        return redirect()
            ->route('submission-conversations.reflection.show', [$tenant, $reflectionResponse])
            ->with('status', 'Message sent.');
    }

    public function showLesson(Request $request, Tenant $tenant, LessonProgress $lessonProgress): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($lessonProgress->tenant_id === $tenant->id, 404);
        abort_unless($this->conversations->canView($user, $tenant, $lessonProgress), 403);

        $lessonProgress->load([
            'user:id,name,email',
            'lesson' => fn ($q) => $q->with(['module.course', 'course']),
        ]);

        $messages = $this->conversations->messagesFor($lessonProgress);
        $byId = $messages->keyBy('id');

        return view('submission-conversation.lesson', [
            'tenant' => $tenant,
            'subject' => $lessonProgress,
            'lesson' => $lessonProgress->lesson,
            'learner' => $lessonProgress->user,
            'messages' => $messages,
            'messageById' => $byId,
            'messageDepth' => $this->messageDepthMap($messages, $byId),
            'isStaff' => $this->conversations->isStaff($user, $tenant),
        ]);
    }

    public function storeLesson(Request $request, Tenant $tenant, LessonProgress $lessonProgress): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($lessonProgress->tenant_id === $tenant->id, 404);
        abort_unless($this->conversations->canPost($user, $tenant, $lessonProgress), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:65535'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->conversations->createMessage(
            $tenant,
            $lessonProgress,
            $user,
            $validated['body'],
            $validated['parent_id'] ?? null,
        );

        return redirect()
            ->route('submission-conversations.lesson.show', [$tenant, $lessonProgress])
            ->with('status', 'Message sent.');
    }

    /**
     * @param  Collection<int, SubmissionConversationMessage>  $messages
     * @param  Collection<int, SubmissionConversationMessage>  $byId
     * @return array<int, int>
     */
    private function messageDepthMap(Collection $messages, Collection $byId): array
    {
        $memo = [];
        $walk = function (int $id) use (&$walk, &$memo, $byId): int {
            if (isset($memo[$id])) {
                return $memo[$id];
            }
            $m = $byId->get($id);
            if ($m === null) {
                return $memo[$id] = 0;
            }
            if ($m->parent_id === null) {
                return $memo[$id] = 0;
            }

            return $memo[$id] = 1 + $walk((int) $m->parent_id);
        };
        foreach ($messages as $m) {
            $walk((int) $m->id);
        }

        return $memo;
    }
}
