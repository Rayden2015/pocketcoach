<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskBoardImportDedupeTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_skips_existing_checklist_case_titles_on_list(): void
    {
        config([
            'task_board.driver' => 'trello',
            'task_board.trello.api_key' => 'k',
            'task_board.trello.token' => 't',
            'task_board.trello.board_id' => '',
            'task_board.trello.default_list_id' => 'list-target',
        ]);

        Http::fake([
            'https://api.trello.com/1/lists/list-target/cards*' => Http::response([
                ['name' => 'QA H1: Home lists discoverable spaces'],
            ], 200),
            'https://api.trello.com/1/cards' => Http::response([
                'id' => 'card-new',
                'shortUrl' => 'https://trello.com/c/new',
            ], 201),
        ]);

        $this->artisan('task-board:import-qa-checklist', [
            '--ids' => 'H1,H2',
            '--force' => true,
        ])->assertSuccessful();

        $cardPosts = 0;
        foreach (Http::recorded() as [$request, $_response]) {
            if ($request->url() === 'https://api.trello.com/1/cards') {
                $cardPosts++;
                $this->assertStringContainsString('QA H2:', (string) $request['name']);
            }
        }

        $this->assertSame(1, $cardPosts);
    }

    public function test_import_no_dedupe_creates_both_when_existing_h1(): void
    {
        config([
            'task_board.driver' => 'trello',
            'task_board.trello.api_key' => 'k',
            'task_board.trello.token' => 't',
            'task_board.trello.board_id' => '',
            'task_board.trello.default_list_id' => 'list-target',
        ]);

        Http::fake([
            'https://api.trello.com/1/lists/list-target/cards*' => Http::response([
                ['name' => 'QA H1: Home lists discoverable spaces'],
            ], 200),
            'https://api.trello.com/1/cards' => Http::response([
                'id' => 'card-x',
                'shortUrl' => 'https://trello.com/c/x',
            ], 201),
        ]);

        $this->artisan('task-board:import-qa-checklist', [
            '--ids' => 'H1,H2',
            '--force' => true,
            '--no-dedupe' => true,
        ])->assertSuccessful();

        $cardPosts = 0;
        foreach (Http::recorded() as [$request, $_response]) {
            if ($request->url() === 'https://api.trello.com/1/cards') {
                $cardPosts++;
            }
        }

        $this->assertSame(2, $cardPosts);
    }
}
