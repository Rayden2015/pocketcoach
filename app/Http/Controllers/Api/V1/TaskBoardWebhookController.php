<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\TaskBoard\TaskBoardGateway;
use App\Http\Controllers\Controller;
use App\Services\TaskBoard\TaskCreationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskBoardWebhookController extends Controller
{
    public function store(Request $request, TaskBoardGateway $taskBoard): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:16000'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['string', 'max:2000'],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['nullable', 'string', 'max:2000'],
            'list_id' => ['nullable', 'string', 'max:128'],
        ]);

        /** @var array<string, string> $meta */
        $meta = [];
        if (isset($validated['metadata']) && is_array($validated['metadata'])) {
            foreach ($validated['metadata'] as $k => $v) {
                if (is_string($k) && (is_string($v) || is_numeric($v))) {
                    $meta[$k] = (string) $v;
                }
            }
        }

        $creation = new TaskCreationRequest(
            title: $validated['title'],
            description: $validated['description'] ?? '',
            checklistItems: $validated['checklist'] ?? [],
            metadata: $meta,
            listId: $validated['list_id'] ?? null,
        );

        $result = $taskBoard->createTask($creation);

        return response()->json([
            'id' => $result->id,
            'url' => $result->url,
            'provider' => $result->provider,
        ], 201);
    }
}
