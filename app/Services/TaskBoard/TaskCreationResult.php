<?php

namespace App\Services\TaskBoard;

final readonly class TaskCreationResult
{
    public function __construct(
        public string $id,
        public string $url,
        public string $provider,
    ) {}
}
