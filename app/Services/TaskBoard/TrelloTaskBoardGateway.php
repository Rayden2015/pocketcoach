<?php

namespace App\Services\TaskBoard;

use App\Contracts\TaskBoard\TaskBoardGateway;
use App\Services\TaskBoard\Exceptions\TaskBoardException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TrelloTaskBoardGateway implements TaskBoardGateway
{
    private ?string $cachedFirstListId = null;

    public function __construct(
        private string $apiKey,
        private string $token,
        private string $explicitListId,
        private string $boardId,
        private string $baseUrl = 'https://api.trello.com/1',
    ) {}

    public function createTask(TaskCreationRequest $request): TaskCreationResult
    {
        $listId = $this->resolveListId($request->listId);

        if ($listId === '' || $this->apiKey === '' || $this->token === '') {
            throw TaskBoardException::fromHttp('Trello is not configured (list id, API key, or token missing)');
        }

        $payload = [
            'key' => $this->apiKey,
            'token' => $this->token,
            'idList' => $listId,
            'name' => $request->title,
            'desc' => $request->compiledDescription(),
        ];

        $response = Http::asForm()
            ->timeout(15)
            ->post($this->baseUrl.'/cards', $payload);

        $this->logTrelloExchange(
            'cards.create',
            [
                'method' => 'POST',
                'url' => $this->baseUrl.'/cards',
                'form' => [
                    'key' => '(redacted)',
                    'token' => '(redacted)',
                    'idList' => $listId,
                    'name' => $request->title,
                    'desc_chars' => mb_strlen($payload['desc']),
                ],
            ],
            $response,
        );

        if (! $response->successful()) {
            throw TaskBoardException::fromHttp(
                'Trello create card failed',
                $response->body(),
            );
        }

        $json = $response->json();

        if (! is_array($json) || ! isset($json['id'])) {
            throw TaskBoardException::fromHttp('Trello returned an unexpected payload', $response->body());
        }

        $url = (string) ($json['shortUrl'] ?? $json['url'] ?? 'https://trello.com/c/'.$json['id']);

        return new TaskCreationResult(
            id: (string) $json['id'],
            url: $url,
            provider: 'trello',
        );
    }

    public function isEnabled(): bool
    {
        $hasTarget = $this->explicitListId !== '' || $this->boardId !== '';

        return $this->apiKey !== ''
            && $this->token !== ''
            && $hasTarget;
    }

    /**
     * Case ids (uppercase) for cards on the target list whose names match checklist imports: "QA H1: …".
     * Used to avoid duplicate cards when re-running `task-board:import-qa-checklist`.
     *
     * @return array<string, true>
     */
    public function existingQaChecklistCaseIdsOnTargetList(?string $listIdOverride = null): array
    {
        try {
            $listId = $this->resolveListId($listIdOverride);
        } catch (TaskBoardException) {
            return [];
        }

        if ($listId === '' || $this->apiKey === '' || $this->token === '') {
            return [];
        }

        $url = $this->baseUrl.'/lists/'.$listId.'/cards';
        $response = Http::timeout(30)->get($url, [
            'key' => $this->apiKey,
            'token' => $this->token,
            'fields' => 'name',
            'limit' => 1000,
        ]);

        $this->logTrelloExchange(
            'lists.cards',
            [
                'method' => 'GET',
                'url' => $url,
                'query' => [
                    'key' => '(redacted)',
                    'token' => '(redacted)',
                    'list_id' => $listId,
                    'limit' => 1000,
                ],
            ],
            $response,
        );

        if (! $response->successful()) {
            Log::warning('trello.fetch_list_cards_failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 2000),
            ]);

            return [];
        }

        /** @var mixed $cards */
        $cards = $response->json();
        if (! is_array($cards)) {
            return [];
        }

        $ids = [];
        foreach ($cards as $card) {
            if (! is_array($card) || empty($card['name']) || ! is_string($card['name'])) {
                continue;
            }
            $caseId = $this->parseImportedCaseIdFromTitle($card['name']);
            if ($caseId !== null) {
                $ids[$caseId] = true;
            }
        }

        return $ids;
    }

    private function parseImportedCaseIdFromTitle(string $name): ?string
    {
        if (preg_match('/^QA\s+([A-Za-z]{1,6}\d+):/u', $name, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    private function resolveListId(?string $requestListId): string
    {
        if ($requestListId !== null && $requestListId !== '') {
            return $requestListId;
        }

        if ($this->explicitListId !== '') {
            return $this->explicitListId;
        }

        if ($this->boardId === '') {
            return '';
        }

        if ($this->cachedFirstListId !== null) {
            return $this->cachedFirstListId;
        }

        $listPath = $this->baseUrl.'/boards/'.$this->boardId.'/lists';
        $query = [
            'key' => $this->apiKey,
            'token' => $this->token,
        ];

        $response = Http::timeout(15)
            ->get($listPath, $query);

        $this->logTrelloExchange(
            'boards.lists',
            [
                'method' => 'GET',
                'url' => $listPath,
                'query' => ['key' => '(redacted)', 'token' => '(redacted)', 'board_id' => $this->boardId],
            ],
            $response,
        );

        if (! $response->successful()) {
            throw TaskBoardException::fromHttp(
                'Trello fetch board lists failed',
                $response->body(),
            );
        }

        /** @var mixed $lists */
        $lists = $response->json();

        if (! is_array($lists) || $lists === []) {
            throw TaskBoardException::fromHttp('Trello board has no lists (add a list to the board)');
        }

        usort($lists, function (mixed $a, mixed $b): int {
            $posA = is_array($a) ? (float) ($a['pos'] ?? 0) : 0;
            $posB = is_array($b) ? (float) ($b['pos'] ?? 0) : 0;

            return $posA <=> $posB;
        });

        $first = $lists[0];
        if (! is_array($first) || empty($first['id'])) {
            throw TaskBoardException::fromHttp('Trello returned an unexpected lists payload', $response->body());
        }

        $this->cachedFirstListId = (string) $first['id'];

        return $this->cachedFirstListId;
    }

    /**
     * @param  array<string, mixed>  $requestLog
     */
    private function logTrelloExchange(string $operation, array $requestLog, Response $response): void
    {
        $max = max(500, (int) config('task_board.trello.log_http_body_max', 12_000));
        $body = $response->body();
        $bodyPreview = $body === '' ? '' : mb_substr($body, 0, $max);
        if (mb_strlen($body) > $max) {
            $bodyPreview .= '…(truncated)';
        }

        $meta = [
            'operation' => $operation,
            'request' => $requestLog,
            'response' => [
                'status' => $response->status(),
                'body' => $bodyPreview,
            ],
        ];

        $verbose = (bool) config('task_board.trello.log_http', false);

        if ($verbose) {
            Log::info('trello.http', $meta);

            return;
        }

        if (! $response->successful()) {
            Log::warning('trello.http_error', $meta);
        }
    }
}
