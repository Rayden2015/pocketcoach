# Artisan command sheet (LMS)

Application-specific Artisan commands live under `app/Console/Commands/` and in `routes/console.php`. Run from the project root:

```bash
php artisan <command> [arguments] [options]
```

Help for any command:

```bash
php artisan help <command>
```

## Scheduled work

`reflections:publish-due` is registered in `routes/console.php` to run **every minute** via Laravel’s scheduler.

Ensure the server runs the scheduler (production example — adjust path and PHP binary):

```bash
* * * * * cd /path/to/lms && php artisan schedule:run >> /dev/null 2>&1
```

Without a working scheduler, scheduled reflection prompts will not flip to published automatically.

---

## `reflections:publish-due`

| | |
| --- | --- |
| **Class** | `App\Console\Commands\PublishDueReflectionPrompts` |
| **Purpose** | Finds reflection prompts that are not yet published, have `scheduled_publish_at` set, and that time is in the past; sets `is_published` to true and `published_at` to the scheduled time. |
| **Arguments** | None |
| **Options** | None |

**Examples**

```bash
php artisan reflections:publish-due
```

**Notes**

- Safe to run repeatedly; only due rows are updated.
- On success with work done, prints how many reflections were published.
- Requires database access and the `reflection_prompts` schema your migrations define.

---

## `task-board:create-task`

| | |
| --- | --- |
| **Class** | `App\Console\Commands\TaskBoardCreateTaskCommand` |
| **Purpose** | Creates a single card on the configured task board (Trello when enabled) or simulates creation with the null driver. |
| **Arguments** | `title` — Card title (required) |
| **Options** | `--description=` — Body (markdown or plain)<br>`--checklist=*` — Repeat for each checklist line<br>`--meta=*` — Repeat as `key=value` metadata<br>`--list-id=` — Override Trello list id<br>`--dry-run` — Use null driver; no live API calls |

**Examples**

```bash
# Dry run (no Trello)
php artisan task-board:create-task "Smoke: login" \
  --description="Try email login." \
  --checklist="Open /login" --checklist="Submit form" \
  --meta=case_id=DEMO1 \
  --dry-run

# Live (requires Trello env — see below)
php artisan task-board:create-task "Release checklist item"
```

**Configuration** (`config/task_board.php`)

| Variable | Role |
| --- | --- |
| `TASK_BOARD_DRIVER` | `trello` for live API, `null` otherwise (default `null`) |
| `TRELLO_API_KEY`, `TRELLO_TOKEN` | Trello credentials |
| `TRELLO_BOARD_ID` | Board short id; used with first list if `TRELLO_DEFAULT_LIST_ID` empty |
| `TRELLO_DEFAULT_LIST_ID` | Explicit list for new cards |
| `TRELLO_LOG_HTTP` | Optional HTTP debug logging |

After changing `.env`, run `php artisan config:clear` (or avoid config caching during setup).

---

## `task-board:import-qa-checklist`

| | |
| --- | --- |
| **Class** | `App\Console\Commands\TaskBoardImportQaChecklistCommand` |
| **Purpose** | Parses a markdown QA checklist (default `docs/QA_E2E_CHECKLIST.md`) and creates one Trello card per test-case row. |
| **Arguments** | None |
| **Options** | `--file=` — Path relative to project root (default `docs/QA_E2E_CHECKLIST.md`)<br>`--section=` — Only rows under a `##` heading containing this substring (case-insensitive)<br>`--ids=` — Comma-separated case ids (e.g. `H1,H2,API1`)<br>`--limit=` — Stop after N cards<br>`--dry-run` — Null driver only<br>`--force` — Skip confirmation when not dry-run<br>`--no-dedupe` — Create cards even if case id already exists on the target list (Trello only) |

**Examples**

```bash
# Preview import (no API)
php artisan task-board:import-qa-checklist --dry-run

# Import only API cases
php artisan task-board:import-qa-checklist --ids=API1,API2 --force

# Different file, subsection filter
php artisan task-board:import-qa-checklist --file=docs/QA_E2E_CHECKLIST.md --section="Learner" --limit=5 --dry-run
```

**Behaviour**

- Prints progress per row; between Trello creates, sleeps ~150ms to reduce rate issues.
- **Dedupe** (default, Trello only): skips case ids already present on the target list unless `--no-dedupe`.
- If not `--dry-run` and not `--force`, prompts for confirmation before creating cards.
- When Trello is not configured, warns and still completes using whatever gateway resolves (URLs may be placeholders until live).

---

## `inspire`

| | |
| --- | --- |
| **Definition** | Closure in `routes/console.php` |
| **Purpose** | Prints a random “inspiring” quote via Laravel’s `Inspiring` facade (bootstrap / demo). |
| **Arguments / options** | None |

**Example**

```bash
php artisan inspire
```

---

## Browser tests (Laravel Dusk)

End-to-end tests live in `tests/Browser/` and use `phpunit.dusk.xml`. They drive a real browser against a **running** app (`php artisan serve` is not started for you).

**One-time setup**

1. Copy `.env.dusk.example` to `.env.dusk.local` and set `APP_KEY` (same as `.env` is fine) and any other secrets your app needs.
2. `touch database/dusk.sqlite`
3. Build front-end assets so `@vite` resolves (`npm run build`), unless you already have `public/build/`.
4. Install / update ChromeDriver if needed: `php artisan dusk:chrome-driver`

**Run (two terminals)**

The dev server must use the **same** `APP_URL`, `APP_KEY`, and `DB_*` as Dusk (see `.env.dusk.local` and `phpunit.dusk.xml`). If your normal `.env` points at a different database file than `database/dusk.sqlite`, copy `.env.dusk.local` over `.env` before `serve`, or align those variables for the session.

Terminal A — same URL as `APP_URL` (default `http://127.0.0.1:8000`):

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal B:

```bash
composer dusk
# or: php artisan dusk
```

`php artisan dusk` temporarily swaps in `.env.dusk.local` as `.env` while PHPUnit runs, then restores your backup. Each browser test runs `migrate:fresh` against the Dusk SQLite file so the server always talks to the same path; avoid pointing another environment at `database/dusk.sqlite` while tests run.

---

## Quick reference

| Command | Typical use |
| --- | --- |
| `reflections:publish-due` | Cron/scheduler + manual runs to publish due reflections |
| `task-board:create-task` | One-off QA or ops cards |
| `task-board:import-qa-checklist` | Bulk import from markdown checklist |
| `inspire` | Sanity check that Artisan runs |
| `dusk` (Composer) / `php artisan dusk` | Laravel Dusk browser tests (needs dev server + `.env.dusk.local`) |
