<?php

namespace App\Services\TaskBoard;

use App\Contracts\TaskBoard\TaskBoardGateway;
use Illuminate\Support\Facades\Log;

final class NullTaskBoardGateway implements TaskBoardGateway
{
    public function createTask(TaskCreationRequest $request): TaskCreationResult
    {
        $id = 'null-'.bin2hex(random_bytes(6));

        // Use debug to avoid huge `laravel.log` when importing dozens of QA rows with driver still `null`.
        Log::debug('task_board.null.create_task', [
            'title' => $request->title,
        ]);

        return new TaskCreationResult(
            id: $id,
            url: '#',
            provider: 'null',
        );
    }

    public function isEnabled(): bool
    {
        return false;
    }
}
