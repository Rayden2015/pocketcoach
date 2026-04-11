<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use App\Models\ReflectionPrompt;
use App\Models\ReflectionResponse;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearnerSubmissionController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $tab = $request->query('tab', 'reflections');
        if (! in_array($tab, ['reflections', 'lessons'], true)) {
            $tab = 'reflections';
        }

        $rawPrompt = $request->query('prompt');
        $promptId = null;
        if ($rawPrompt !== null && $rawPrompt !== '' && ctype_digit((string) $rawPrompt)) {
            $promptId = (int) $rawPrompt;
        }

        $promptsForFilter = ReflectionPrompt::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get(['id', 'title', 'published_at', 'is_published']);

        $responses = null;
        $lessonProgress = null;

        if ($tab === 'reflections') {
            $query = ReflectionResponse::query()
                ->whereHas('reflectionPrompt', fn ($q) => $q->where('tenant_id', $tenant->id))
                ->with(['user', 'reflectionPrompt'])
                ->withCount('conversationMessages')
                ->orderByDesc('updated_at');

            if ($promptId !== null) {
                $query->where('reflection_prompt_id', $promptId);
            }

            $responses = $query->paginate(25)->withQueryString();
        } else {
            $lessonQuery = LessonProgress::query()
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('notes')
                ->where('notes', '!=', '')
                ->with([
                    'user',
                    'lesson' => fn ($q) => $q->with(['module.course', 'course']),
                ])
                ->withCount('conversationMessages')
                ->orderByDesc('updated_at');

            $lessonProgress = $lessonQuery->paginate(25, ['*'], 'lesson_page')->withQueryString();
        }

        return view('coach.learner-submissions', [
            'tenant' => $tenant,
            'tab' => $tab,
            'responses' => $responses,
            'lessonProgress' => $lessonProgress,
            'promptsForFilter' => $promptsForFilter,
            'selectedPromptId' => $promptId,
        ]);
    }
}
