<?php

namespace App\Console\Commands;

use App\Contracts\TaskBoard\TaskBoardGateway;
use App\Services\TaskBoard\NullTaskBoardGateway;
use App\Services\TaskBoard\TaskCreationRequest;
use Illuminate\Console\Command;

class TaskBoardCreateTaskCommand extends Command
{
    protected $signature = 'task-board:create-task
                            {title : Card title}
                            {--description= : Markdown or plain body}
                            {--checklist=* : Repeated option for checklist lines}
                            {--meta=* : key=value metadata rows}
                            {--list-id= : Override Trello list id}
                            {--dry-run : Log only; do not call Trello}';

    protected $description = 'Create a QA / task-board card (Trello when configured)';

    public function handle(): int
    {
        $meta = [];
        foreach ((array) $this->option('meta') as $row) {
            if (! is_string($row) || ! str_contains($row, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $row, 2);
            $k = trim($k);
            if ($k !== '') {
                $meta[$k] = trim($v);
            }
        }

        $request = new TaskCreationRequest(
            title: (string) $this->argument('title'),
            description: (string) ($this->option('description') ?? ''),
            checklistItems: array_values(array_filter((array) $this->option('checklist'), 'is_string')),
            metadata: $meta,
            listId: $this->option('list-id') ?: null,
        );

        if ($this->option('dry-run')) {
            $gateway = new NullTaskBoardGateway;
        } else {
            $gateway = app(TaskBoardGateway::class);
        }

        $result = $gateway->createTask($request);

        $this->components->info('Task created.');
        $this->line('  Provider: '.$result->provider);
        $this->line('  Id: '.$result->id);
        $this->line('  Url: '.$result->url);

        return self::SUCCESS;
    }
}
