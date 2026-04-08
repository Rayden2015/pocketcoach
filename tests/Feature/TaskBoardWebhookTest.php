<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskBoardWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_qa_tasks_route_is_hidden_without_incoming_secret(): void
    {
        config(['task_board.incoming_secret' => null]);

        $this->postJson('/api/v1/integrations/qa-tasks', [
            'title' => 'Test',
        ])->assertNotFound();
    }

    public function test_qa_tasks_rejects_bad_secret(): void
    {
        config(['task_board.incoming_secret' => 'correct-secret']);

        $this->postJson('/api/v1/integrations/qa-tasks', [
            'title' => 'Test',
        ], [
            'Authorization' => 'Bearer wrong',
        ])->assertForbidden();
    }

    public function test_qa_tasks_creates_task_with_null_driver(): void
    {
        config([
            'task_board.incoming_secret' => 'correct-secret',
            'task_board.driver' => 'null',
        ]);

        $response = $this->postJson('/api/v1/integrations/qa-tasks', [
            'title' => 'QA: Smoke test',
            'description' => 'Hello',
            'checklist' => ['Step one', 'Step two'],
            'metadata' => ['pr' => '123'],
        ], [
            'Authorization' => 'Bearer correct-secret',
        ]);

        $response->assertCreated()
            ->assertJsonPath('provider', 'null');
        $this->assertIsString($response->json('id'));
    }

    public function test_qa_tasks_with_trello_driver_fakes_http(): void
    {
        config([
            'task_board.incoming_secret' => 's',
            'task_board.driver' => 'trello',
            'task_board.trello.api_key' => 'k',
            'task_board.trello.token' => 't',
            'task_board.trello.default_list_id' => 'list1',
        ]);

        Http::fake([
            'https://api.trello.com/1/cards' => Http::response([
                'id' => 'card-abc',
                'shortUrl' => 'https://trello.com/c/x',
            ], 201),
        ]);

        $response = $this->postJson('/api/v1/integrations/qa-tasks', [
            'title' => 'From CI',
        ], [
            'X-Task-Board-Secret' => 's',
        ]);

        $response->assertCreated()
            ->assertJsonPath('id', 'card-abc')
            ->assertJsonPath('provider', 'trello');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.trello.com/1/cards'
                && $request['name'] === 'From CI';
        });
    }

    public function test_qa_tasks_trello_uses_first_list_when_only_board_id_set(): void
    {
        config([
            'task_board.incoming_secret' => 's',
            'task_board.driver' => 'trello',
            'task_board.trello.api_key' => 'k',
            'task_board.trello.token' => 't',
            'task_board.trello.default_list_id' => '',
            'task_board.trello.board_id' => 'bMdkBfK9',
        ]);

        Http::fake([
            'https://api.trello.com/1/boards/bMdkBfK9/lists*' => Http::response([
                ['id' => 'list-second', 'pos' => 98_304.0],
                ['id' => 'list-first', 'pos' => 16_384.0],
            ], 200),
            'https://api.trello.com/1/cards' => Http::response([
                'id' => 'card-xyz',
                'shortUrl' => 'https://trello.com/c/y',
            ], 201),
        ]);

        $response = $this->postJson('/api/v1/integrations/qa-tasks', [
            'title' => 'Board-only config',
        ], [
            'X-Task-Board-Secret' => 's',
        ]);

        $response->assertCreated()
            ->assertJsonPath('id', 'card-xyz');

        Http::assertSent(function ($request): bool {
            if ($request->url() !== 'https://api.trello.com/1/cards') {
                return false;
            }

            return ($request['idList'] ?? null) === 'list-first';
        });
    }
}
