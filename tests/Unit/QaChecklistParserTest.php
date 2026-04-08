<?php

namespace Tests\Unit;

use App\Services\TaskBoard\QaChecklistParser;
use PHPUnit\Framework\TestCase;

class QaChecklistParserTest extends TestCase
{
    public function test_parses_four_column_rows_and_prerequisites(): void
    {
        $md = <<<'MD'
## 1. Platform home

| ID | Scenario | Steps | Expected |
|----|----------|-------|----------|
| H1 | Home lists | Open `/` | **200** |
| H2 | Hidden | Draft only | Not listed |

## 0. Prerequisites

| # | Check |
|---|--------|
| P1 | SQLite |
| P2 | Two tenants |
MD;

        $parser = new QaChecklistParser;
        $rows = $parser->parse($md);

        $this->assertCount(4, $rows);
        $this->assertSame('H1', $rows[0]['id']);
        $this->assertSame('Open `/`', $rows[0]['steps']);
        $this->assertSame('**200**', $rows[0]['expected']);
        $this->assertSame('P1', $rows[2]['id']);
        $this->assertStringContainsString('SQLite', $rows[2]['expected']);
    }

    public function test_parses_three_column_regression_rows(): void
    {
        $md = <<<'MD'
## 11. Regression

| ID | Scenario | Expected |
|----|----------|----------|
| E1 | Suspended tenant | Excluded from home |
MD;

        $parser = new QaChecklistParser;
        $rows = $parser->parse($md);

        $this->assertCount(1, $rows);
        $this->assertSame('E1', $rows[0]['id']);
        $this->assertSame('', $rows[0]['steps']);
        $this->assertStringContainsString('Excluded', $rows[0]['expected']);
    }
}
