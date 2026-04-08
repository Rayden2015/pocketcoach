<?php

namespace App\Services\TaskBoard;

final class QaChecklistParser
{
    /**
     * @return list<array{
     *   id: string,
     *   section: string,
     *   scenario: string,
     *   steps: string,
     *   expected: string
     * }>
     */
    public function parse(string $markdown): array
    {
        $lines = preg_split('/\R/', $markdown) ?: [];
        $sectionTitle = 'Intro';
        $cases = [];

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (preg_match('/^##\s+(.+)$/', $line, $m)) {
                $sectionTitle = trim($m[1]);

                continue;
            }

            if (! $this->isTableRow($line)) {
                continue;
            }

            $cells = $this->rowCells($line);
            if ($cells === []) {
                continue;
            }

            if ($this->isSeparatorRow($cells)) {
                continue;
            }

            if ($this->isHeaderRow($cells)) {
                continue;
            }

            $row = $this->normalizeRow($cells, $sectionTitle);
            if ($row !== null) {
                $cases[] = $row;
            }
        }

        return $cases;
    }

    /**
     * @param  list<string>  $cells
     */
    private function normalizeRow(array $cells, string $sectionTitle): ?array
    {
        if (count($cells) === 2) {
            [$a, $b] = $cells;
            if (preg_match('/^P\d+$/i', $a)) {
                return [
                    'id' => $a,
                    'section' => $sectionTitle,
                    'scenario' => 'Prerequisite',
                    'steps' => '',
                    'expected' => $b,
                ];
            }

            return null;
        }

        if (count($cells) === 3) {
            [$id, $scenario, $expected] = $cells;
            if (! $this->looksLikeCaseId($id)) {
                return null;
            }

            return [
                'id' => $id,
                'section' => $sectionTitle,
                'scenario' => $scenario,
                'steps' => '',
                'expected' => $expected,
            ];
        }

        if (count($cells) >= 4) {
            [$id, $scenario, $steps, $expected] = [
                $cells[0],
                $cells[1],
                $cells[2],
                implode(' | ', array_slice($cells, 3)),
            ];
            if (! $this->looksLikeCaseId($id)) {
                return null;
            }

            return [
                'id' => $id,
                'section' => $sectionTitle,
                'scenario' => $scenario,
                'steps' => $steps,
                'expected' => $expected,
            ];
        }

        return null;
    }

    private function looksLikeCaseId(string $id): bool
    {
        return $id !== ''
            && $id !== 'ID'
            && $id !== '#'
            && (bool) preg_match('/^[A-Za-z]{1,6}\d+$/', $id);
    }

    /**
     * @param  list<string>  $cells
     */
    private function isSeparatorRow(array $cells): bool
    {
        foreach ($cells as $c) {
            $t = trim($c);
            if ($t === '') {
                return false;
            }
            if (! preg_match('/^:?-{3,}:?$/', $t)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $cells
     */
    private function isHeaderRow(array $cells): bool
    {
        $first = strtolower(trim($cells[0] ?? ''));

        return in_array($first, ['id', '#'], true);
    }

    private function isTableRow(string $line): bool
    {
        $line = trim($line);

        return str_starts_with($line, '|') && str_ends_with($line, '|');
    }

    /**
     * @return list<string>
     */
    private function rowCells(string $line): array
    {
        $inner = trim(trim($line), '|');

        return array_map('trim', explode('|', $inner));
    }
}
