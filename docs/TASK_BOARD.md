# Task board integration (portable QA automation)

Small **driver-based** layer for creating tester tasks (cards) from PHP—default driver is **Trello**; use **`null`** locally to avoid network calls.

## Reusing in another Laravel project

1. Copy **directories / files** (adjust namespaces if not `App\`):
   - `app/Contracts/TaskBoard/TaskBoardGateway.php`
   - `app/Services/TaskBoard/` (all classes)
   - `app/Http/Middleware/ValidateTaskBoardWebhookSecret.php`
   - `app/Http/Controllers/Api/V1/TaskBoardWebhookController.php` (or adapt route to your API layout)
   - `app/Console/Commands/TaskBoardCreateTaskCommand.php`
   - `app/Console/Commands/TaskBoardImportQaChecklistCommand.php`
   - `config/task_board.php`
2. Register the **`TaskBoardGateway` singleton** (see `AppServiceProvider` in this repo) and the **`task_board.webhook`** middleware alias (see `bootstrap/app.php`).
3. Add the **`POST`** route for automation (see `routes/api.php`).
4. Set **environment variables** (see `.env.example`).

## Trello credentials

1. Log in to Trello → [Power-Up Admin / API key](https://trello.com/power-ups/admin).
2. Create an **API key** and a **member token** with permission to create cards.
3. **Target list:** either set **`TRELLO_BOARD_ID`** to the board’s short id from the URL (`…/b/{id}/…`) and new cards go to the **first list** (leftmost column, by Trello `pos`), or set **`TRELLO_DEFAULT_LIST_ID`** to pin a specific list.

## Environment

| Variable | Purpose |
|----------|---------|
| `TASK_BOARD_DRIVER` | `null` (default) or `trello` |
| `TRELLO_API_KEY` | Trello API key |
| `TRELLO_TOKEN` | Trello token |
| `TRELLO_BOARD_ID` | Board short id; used when `TRELLO_DEFAULT_LIST_ID` is empty (**first list** on the board) |
| `TRELLO_DEFAULT_LIST_ID` | Optional explicit list id (overrides board “first list” behavior) |
| `TRELLO_BASE_URL` | Optional override (default `https://api.trello.com/1`) |
| `TRELLO_LOG_HTTP` | When `true`, logs every Trello request/response to `trello.http` (key/token redacted). Failed responses are also logged at `warning` when this is `false`. |
| `TRELLO_LOG_HTTP_BODY_MAX` | Max response body characters stored in logs (default `12000`). |
| `TASK_BOARD_INCOMING_SECRET` | If set, enables `POST /api/v1/integrations/qa-tasks` and protects it |

If `TASK_BOARD_DRIVER=trello` but credentials are incomplete, the app **falls back** to the null driver and logs a warning.

## HTTP automation (CI, scripts, other services)

**Endpoint:** `POST /api/v1/integrations/qa-tasks`  
**Auth:** `Authorization: Bearer <TASK_BOARD_INCOMING_SECRET>` **or** header `X-Task-Board-Secret: <secret>`  
**Disabled:** If `TASK_BOARD_INCOMING_SECRET` is empty, the route returns **404** (no accidental exposure).

**JSON body:**

```json
{
  "title": "QA: Home lists only active spaces",
  "description": "See docs/QA_E2E_CHECKLIST.md — section 1.",
  "checklist": ["Open /", "Confirm grid", "Note build / env"],
  "metadata": {"branch": "feature/home", "author": "ci"},
  "list_id": "optional-trello-list-override"
}
```

**Response:** `201` with `{ "id", "url", "provider" }`.

## Artisan

```bash
php artisan task-board:create-task "QA: New enrollment flow" \
  --description="Steps in PR #42" \
  --checklist="Register" --checklist="Enroll" --checklist="Lesson progress" \
  --meta=branch=feature/foo

php artisan task-board:create-task "Dry run" --dry-run
```

### Import `docs/QA_E2E_CHECKLIST.md` (one Trello card per test row)

Parses markdown tables (IDs like `H1`, `P1`, `API1`, `E1`, …). Use **`--dry-run`** first (null driver, no HTTP). Use **`--force`** to skip the confirmation prompt (CI / non-interactive).

```bash
php artisan task-board:import-qa-checklist --dry-run --limit=5

php artisan task-board:import-qa-checklist --force --limit=10
php artisan task-board:import-qa-checklist --force --ids=H1,H2,LR1
php artisan task-board:import-qa-checklist --force --section="Platform home"
php artisan task-board:import-qa-checklist --force --file=docs/QA_E2E_CHECKLIST.md
```

**Duplicates:** By default, import **skips** rows whose case id already appears on the **target list** as a card named `QA {ID}: …` (same pattern the importer creates). Use **`--no-dedupe`** if you really want a second card for the same id. Trello is queried once per run (up to 1000 cards on that list).

Requires `TASK_BOARD_DRIVER=trello`, API key + token, and either `TRELLO_BOARD_ID` or `TRELLO_DEFAULT_LIST_ID` when not using `--dry-run`.

### New QA tasks as you ship features

Pick one (or combine):

1. **One-off card (no checklist edit)** — same title style is optional:
   ```bash
   php artisan task-board:create-task "QA: Payments — retry webhook" \
     --description="PR #… steps…" --meta=branch=feature/pay-meta
   ```
2. **HTTP / CI** — `POST /api/v1/integrations/qa-tasks` with JSON `title`, `description`, `checklist`, `metadata` (see above).
3. **Formal checklist row** — add a row to `docs/QA_E2E_CHECKLIST.md` with a new id (e.g. `LR9`), then run import; dedupe only affects rows that **match** `QA {id}:` cards already on the board, so **new** ids always create new cards.

**Note:** Dedupe is based on the **`QA H1:`**-style prefix. Ad-hoc titles like `QA: My feature` are not treated as checklist cases and are never skipped by import.

## Application code

Inject `TaskBoardGateway` and call `createTask(new TaskCreationRequest(...))` after meaningful changes (e.g. in an observer, job, or release listener). Keep titles/descriptions concise; attach links to PRs or checklist docs in `metadata` or `description`.

## Adding another provider

Implement `App\Contracts\TaskBoard\TaskBoardGateway`, register it in the container `match` / factory alongside `trello` and `null`, and add any provider-specific config under `config/task_board.php`.
