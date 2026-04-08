<?php

namespace App\Services\TaskBoard;

final readonly class TaskCreationRequest
{
    /**
     * @param  list<string>  $checklistItems  Rendered into the description (and optionally Trello checklists later).
     * @param  array<string, string>  $metadata  Shown as a key/value footer in the description for traceability.
     */
    public function __construct(
        public string $title,
        public string $description = '',
        public array $checklistItems = [],
        public array $metadata = [],
        public ?string $listId = null,
    ) {}

    public function compiledDescription(): string
    {
        $parts = [];

        if ($this->description !== '') {
            $parts[] = trim($this->description);
        }

        if ($this->checklistItems !== []) {
            $lines = array_map(static fn (string $item): string => '- [ ] '.$item, $this->checklistItems);
            $parts[] = "### Checklist\n\n".implode("\n", $lines);
        }

        if ($this->metadata !== []) {
            $rows = [];
            foreach ($this->metadata as $k => $v) {
                $rows[] = '- **'.str_replace('**', '', (string) $k).':** '.$v;
            }
            $parts[] = "### Meta\n\n".implode("\n", $rows);
        }

        return implode("\n\n", array_filter($parts));
    }
}
