# Production deployment — Pocket Coach (Laravel web + API)

Deploy target: **cPanel** (or any SSH host) using `deploy.sh` at the repo root. Mobile store releases are separate — see [mobile/DEPLOYMENT.md](../mobile/DEPLOYMENT.md).

---

## 1. Pre-deploy (local / CI)

Run before pushing or uploading to the server:

```bash
# From repo root
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # produces public/build/ (gitignored — upload or build on server)
php artisan test                 # 213+ feature tests
```

**Commits to ship (as of audit release):**

| Commit / change | Notes |
|-----------------|-------|
| Audit release (`389567d`) | Tenant suspension, space announcements, booking notifications, FCM API, mobile conversation endpoints |
| Header account menu fix | Native `<details>` dropdown (Profile / Log out); rebuild assets after pull |

**New migrations** (applied by `deploy.sh` → `migrate --force`):

- `2026_08_09_100000_create_space_announcements_table`
- `2026_08_09_100001_create_device_tokens_table`

Earlier unreleased migrations on fresh prod DB will also run in order (course reviews, cover images, etc.).

---

## 2. Push to remote

```bash
git push origin main
```

On the server: pull or upload the same commit(s).

---

## 3. Production `.env` checklist

Copy from `.env.example` on first deploy; then set at minimum:

| Variable | Production value |
|----------|------------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://pocketcoach.africa` (your canonical URL) |
| `APP_KEY` | Set once (`php artisan key:generate`); never rotate without plan |
| `DB_*` | MySQL/PostgreSQL credentials (not SQLite) |
| `SESSION_DRIVER` | `database` (default) |
| `QUEUE_CONNECTION` | `database` — **requires queue worker** (see §5) |
| `CACHE_STORE` | `database` or Redis if available |
| `LOG_LEVEL` | `info` |
| `MAIL_*` | Real SMTP (Mailgun, etc.); set `MAIL_FROM_ADDRESS` on your domain |
| `PAYSTACK_*` | Live keys when enabling checkout |
| `GOOGLE_*` | If Google sign-in is enabled |
| `SENTRY_LARAVEL_DSN` | Recommended for production errors |
| `FCM_SERVER_KEY` | Optional; enables mobile push for registered device tokens |

`deploy.sh` appends `SENTRY_RELEASE=<git-sha>-<utc-time>` each run unless `SKIP_SENTRY_RELEASE=1`.

---

## 4. Run deploy on server

SSH into the app directory (folder containing `artisan` and `deploy.sh`):

```bash
# Typical cPanel PHP 8.3
PHP=/opt/cpanel/ea-php83/root/usr/bin/php bash deploy.sh
```

What `deploy.sh` does:

1. `composer install --no-dev` if `vendor/` missing
2. Creates `.env` from example on first run
3. **`npm ci && npm run build`** only if `public/build/manifest.json` is **missing**
4. `php artisan migrate --force`
5. `php artisan storage:link --force`
6. `php artisan optimize:clear` then `php artisan optimize`
7. Refreshes `SENTRY_RELEASE` in `.env`

**Important — front-end assets:** `public/build/` is gitignored. Either:

- Upload `public/build/` from your machine after `npm run build`, **or**
- Let the server run npm when no manifest exists, **or**
- After JS/CSS changes, delete `public/build/` on the server and re-run deploy (or run `npm run build` manually)

If an old manifest remains, `deploy.sh` skips npm and users may get stale CSS/JS until you rebuild.

**Docroot:** point the domain to the `public/` folder (or use root `.htaccess` → `public/`).

---

## 5. Scheduler and queue (required)

### Scheduler (every minute)

cPanel cron:

```cron
* * * * * cd /home/USER/path/to/lms && /opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Publishes due reflection prompts (`reflections:publish-due`).

### Queue worker

Most notifications implement `ShouldQueue`. With `QUEUE_CONNECTION=database`, email and in-app notifications **will not send** until a worker runs.

**Option A — Supervisor** (preferred):

```ini
[program:pocketcoach-worker]
command=/opt/cpanel/ea-php83/root/usr/bin/php /home/USER/path/to/lms/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=USER
numprocs=1
redirect_stderr=true
stdout_logfile=/home/USER/path/to/lms/storage/logs/worker.log
```

**Option B — cPanel cron** (fallback, less ideal):

```cron
* * * * * cd /home/USER/path/to/lms && /opt/cpanel/ea-php83/root/usr/bin/php artisan queue:work database --stop-when-empty >> /dev/null 2>&1
```

**Option C — sync** (simplest, no worker): set `QUEUE_CONNECTION=sync` — notifications run inline; fine for low traffic, not ideal under load.

After deploy or `.env` changes: `php artisan config:clear` (or rely on `deploy.sh` optimize step).

---

## 6. Post-deploy smoke tests

Replace domain and credentials with production values.

```bash
# Health — home loads
curl -sI https://pocketcoach.africa/ | head -1

# API login (Sanctum)
curl -s -X POST https://pocketcoach.africa/api/v1/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"YOUR_TEST_USER","password":"YOUR_PASSWORD","device_name":"deploy-smoke"}'

# Space catalog (active tenant)
curl -sI https://pocketcoach.africa/northstar/catalog | head -1
```

**Browser (manual):**

- [ ] Log in as learner → header **Learner name** opens **Profile** / **Log out**
- [ ] Coach can create a space announcement; learner sees it under announcements
- [ ] Suspended tenant returns “not available” (not a generic 500)
- [ ] Booking request sends email to coach (if mail configured)
- [ ] Notifications bell shows unread count

Full regression: [QA_E2E_CHECKLIST.md](QA_E2E_CHECKLIST.md).

---

## 7. Mobile app (after API is live)

Ship web/API first. Then build stores with production defines — see [mobile/DEPLOYMENT.md](../mobile/DEPLOYMENT.md):

```bash
flutter build appbundle \
  --dart-define=API_BASE_URL=https://pocketcoach.africa/api
```

Device token registration (`POST /api/v1/device-tokens`) and push require `FCM_SERVER_KEY` on the server.

---

## 8. Rollback

1. Check out previous git commit on server (or re-upload previous release).
2. `PHP=... bash deploy.sh` — migrations are forward-only; only roll back code unless you have a down migration plan.
3. Restore previous `public/build/` if the release changed front-end assets.

---

## Quick reference

| Task | Command |
|------|---------|
| Deploy | `bash deploy.sh` |
| Skip npm on server | `SKIP_NPM=1 bash deploy.sh` (you uploaded `public/build/`) |
| Skip composer | `SKIP_COMPOSER=1 bash deploy.sh` (you uploaded `vendor/`) |
| Publish reflections now | `php artisan reflections:publish-due` |
| Clear caches | `php artisan optimize:clear` |
| View failed jobs | `php artisan queue:failed` |
