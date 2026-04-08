<?php

namespace App\Console\Commands;

use App\Contracts\TaskBoard\TaskBoardGateway;
use App\Services\TaskBoard\NullTaskBoardGateway;
use App\Services\TaskBoard\QaChecklistParser;
use App\Services\TaskBoard\TaskCreationRequest;
use App\Services\TaskBoard\TrelloTaskBoardGateway;
use Illuminate\Console\Command;

class TaskBoardImportQaChecklistCommand extends Command
{
    protected $signature = 'task-board:import-qa-checklist
                            {--file=docs/QA_E2E_CHECKLIST.md : Path to checklist markdown (relative to base path)}
                            {--section= : Only rows under a ## heading containing this substring (case-insensitive)}
                            {--ids= : Comma-separated case ids (e.g. H1,H2,API1)}
                            {--limit= : Stop after creating this many cards}
                            {--dry-run : Use null driver; no Trello HTTP}
                            {--force : Skip confirmation when not dry-run}
                            {--no-dedupe : Create cards even if the same checklist case id already exists on the list}';

    protected $description = 'Create task-board cards from docs/QA_E2E_CHECKLIST.md (one card per test case row)';

    public function handle(QaChecklistParser $parser): int
    {
        $relative = (string) $this->option('file');
        $path = base_path($relative);

        if (! is_readable($path)) {
            $this->components->error('File not readable: '.$path);

            return self::FAILURE;
        }

        $markdown = (string) file_get_contents($path);
        $rows = $parser->parse($markdown);

        $sectionNeedle = $this->option('section');
        if (is_string($sectionNeedle) && $sectionNeedle !== '') {
            $needle = mb_strtolower($sectionNeedle);
            $rows = array_values(array_filter(
                $rows,
                static fn (array $r): bool => str_contains(mb_strtolower($r['section']), $needle),
            ));
        }

        $idsFilter = $this->option('ids');
        if (is_string($idsFilter) && $idsFilter !== '') {
            $set = array_map('trim', explode(',', $idsFilter));
            $set = array_map('strtoupper', array_filter($set));
            $rows = array_values(array_filter(
                $rows,
                static fn (array $r): bool => in_array(mb_strtoupper($r['id']), $set, true),
            ));
        }

        $limit = $this->option('limit');
        if ($limit !== null && $limit !== '' && is_numeric($limit)) {
            $rows = array_slice($rows, 0, (int) $limit);
        }

        if ($rows === []) {
            $this->components->warn('No matching checklist rows.');

            return self::SUCCESS;
        }

        $count = count($rows);
        $this->info("Queued {$count} card(s).");

        if (! $this->option('dry-run') && ! $this->option('force')) {
            if (! $this->confirm('Create these cards on the configured task board?', false)) {
                $this->components->warn('Aborted.');

                return self::SUCCESS;
            }
        }

        $gateway = $this->option('dry-run')
            ? new NullTaskBoardGateway
            : app(TaskBoardGateway::class);

        if ($this->option('dry-run')) {
            $this->comment('Dry-run: null driver only — no Trello requests.');
        } elseif (! $gateway->isEnabled()) {
            $this->components->warn(
                'Trello is not active: set TASK_BOARD_DRIVER=trello, TRELLO_API_KEY, TRELLO_TOKEN, and either TRELLO_BOARD_ID (first list used) or TRELLO_DEFAULT_LIST_ID, then run php artisan config:clear. '.
                'Output URLs are "#" until the live driver is enabled (check storage/logs if you expected Trello).'
            );
        }

        $existingCaseIds = [];
        if (
            ! $this->option('dry-run')
            && ! $this->option('no-dedupe')
            && $gateway instanceof TrelloTaskBoardGateway
        ) {
            $existingCaseIds = $gateway->existingQaChecklistCaseIdsOnTargetList(null);
            if ($existingCaseIds !== []) {
                $this->comment(
                    'Dedupe: skipping case ids already on the target list ('.count($existingCaseIds).' known). Use --no-dedupe to force duplicates.'
                );
            }
        }

        $created = [];
        $skipped = 0;

        foreach ($rows as $idx => $row) {
            $caseKey = mb_strtoupper($row['id']);
            if ($existingCaseIds !== [] && isset($existingCaseIds[$caseKey])) {
                $skipped++;
                $this->line(sprintf('  [%d/%d] %s → skip (already on board)', $idx + 1, $count, $row['id']));

                continue;
            }

            $title = 'QA '.$row['id'].': '.$row['scenario'];
            if (mb_strlen($title) > 500) {
                $title = mb_substr($title, 0, 497).'…';
            }

            $descParts = ['**Section:** '.$row['section']];
            if ($row['steps'] !== '') {
                $descParts[] = "### Steps\n\n".$row['steps'];
            }
            if ($row['expected'] !== '') {
                $descParts[] = "### Expected\n\n".$row['expected'];
            }
            $descParts[] = '_Imported from `'.str_replace(base_path().'/', '', $path).'`._';

            $request = new TaskCreationRequest(
                title: $title,
                description: implode("\n\n", $descParts),
                checklistItems: $this->checklistForRow($row),
                metadata: [
                    'case_id' => $row['id'],
                    'section' => $row['section'],
                    'source' => 'qa-checklist',
                ],
                listId: null,
            );

            try {
                $result = $gateway->createTask($request);
            } catch (\Throwable $e) {
                $this->components->error('Failed at '.$row['id'].': '.$e->getMessage());

                return self::FAILURE;
            }

            $created[] = $result;
            $this->line(sprintf('  [%d/%d] %s → %s', $idx + 1, $count, $row['id'], $result->url));

            if (! $this->option('dry-run') && $result->provider === 'trello') {
                usleep(150_000);
            }
        }

        $this->newLine();
        $msg = 'Done. Created '.count($created).' card(s).';
        if ($skipped > 0) {
            $msg .= ' Skipped '.$skipped.' duplicate(s).';
        }
        $this->components->info($msg);

        return self::SUCCESS;
    }

    /**
     * @param  array{id: string, section: string, scenario: string, steps: string, expected: string}  $row
     * @return list<string>
     */
    private function checklistForRow(array $row): array
    {
        $items = [];
        if ($row['steps'] !== '') {
            $items[] = 'Execute steps (see description)';
        }
        if ($row['expected'] !== '') {
            $items[] = 'Verify expected outcome';
        }
        if ($items === []) {
            $items[] = 'Complete verification for '.$row['id'];
        }

        return $items;
    }
}
