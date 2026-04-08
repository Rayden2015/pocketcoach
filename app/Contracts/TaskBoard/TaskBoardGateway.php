<?php

namespace App\Contracts\TaskBoard;

use App\Services\TaskBoard\Exceptions\TaskBoardException;
use App\Services\TaskBoard\TaskCreationRequest;
use App\Services\TaskBoard\TaskCreationResult;

interface TaskBoardGateway
{
    /**
     * Create one task/card on the configured board (e.g. Trello).
     *
     * @throws TaskBoardException
     */
    public function createTask(TaskCreationRequest $request): TaskCreationResult;

    public function isEnabled(): bool;
}
