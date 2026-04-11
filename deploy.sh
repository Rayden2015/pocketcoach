#!/usr/bin/env bash
#
# cPanel / staging — one script after you upload code (SSH: run from this directory).
#
#   bash deploy.sh
#   chmod +x deploy.sh && ./deploy.sh
#
# Optional — PHP binary (cPanel MultiPHP), e.g.:
#   PHP=/opt/cpanel/ea-php83/root/usr/bin/php ./deploy.sh
#
# Optional — skip auto steps:
#   SKIP_COMPOSER=1   Do not run composer (you always upload vendor/)
#   SKIP_NPM=1        Do not auto-run npm build (build locally & upload public/build/)
#   SKIP_OPTIMIZE=1   Clear caches only; skip "php artisan optimize"
#   SKIP_SENTRY_RELEASE=1  Do not write SENTRY_RELEASE to .env (deploy.sh sets git short SHA + UTC time)
#
# -----------------------------------------------------------------------------
# cPanel cron — Laravel scheduler (reflections:publish-due, etc.)
#
#   * * * * * cd /home/USERNAME/PATH_TO_APP && /opt/cpanel/ea-php83/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
#
# "PATH_TO_APP" = folder that contains this file and artisan. Find PHP: which php
# or MultiPHP Manager. Queue workers: use supervisor if available, or QUEUE_CONNECTION=sync.
# -----------------------------------------------------------------------------
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

PHP_BIN="${PHP:-php}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  echo "error: PHP not found (${PHP_BIN}). Set PHP=/path/to/php and try again." >&2
  exit 1
fi

ARTISAN=( "$PHP_BIN" artisan )

echo "=========================================="
echo " cPanel deploy — ${ROOT}"
echo " PHP: $(command -v "$PHP_BIN") ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"
echo "=========================================="

# --- Composer (auto if vendor/ missing) -------------------------------------
if [[ "${SKIP_COMPOSER:-0}" != "1" ]]; then
  if [[ ! -f vendor/autoload.php ]]; then
    if command -v composer >/dev/null 2>&1; then
      echo "==> composer install (--no-dev) — vendor/ was missing"
      composer install --no-dev --optimize-autoloader --no-interaction
    else
      echo "error: vendor/ missing and 'composer' not in PATH. Install Composer or upload vendor/, or set SKIP_COMPOSER=1 if vendor is present." >&2
      exit 1
    fi
  else
    echo "==> composer: vendor/ already present — skipping"
  fi
else
  echo "==> SKIP_COMPOSER=1 — not running composer"
fi

# --- .env (first upload: copy example + key) ----------------------------------
if [[ ! -f .env ]]; then
  if [[ -f .env.example ]]; then
    echo "==> Creating .env from .env.example (first deploy / testing)"
    cp .env.example .env
    "${ARTISAN[@]}" key:generate --force
    echo ""
    echo ">>> Edit .env: set APP_URL, APP_DEBUG=false for public tests, DB_* for MySQL on cPanel."
    echo ""
  else
    echo "error: .env missing and no .env.example found." >&2
    exit 1
  fi
fi

# --- Vite build (auto if npm exists and no manifest yet) ----------------------
vite_manifest_present() {
  [[ -f public/build/manifest.json ]] || [[ -f public/build/.vite/manifest.json ]]
}

if [[ "${SKIP_NPM:-0}" != "1" ]]; then
  if vite_manifest_present; then
    echo "==> Vite: public/build manifest present — skipping npm"
  elif command -v npm >/dev/null 2>&1; then
    echo "==> npm ci && npm run build (manifest missing)"
    npm ci --no-audit --no-fund
    npm run build
  else
    echo "warning: Node/npm not found — build assets on your machine and upload public/build/, or install Node on the server and re-run." >&2
  fi
else
  echo "==> SKIP_NPM=1 — not running npm"
fi

# --- SQLite file for default .env.example -----------------------------------
if grep -q '^DB_CONNECTION=sqlite' .env 2>/dev/null; then
  mkdir -p database
  touch database/database.sqlite 2>/dev/null || true
fi

# --- Sentry release (git short SHA + UTC deploy timestamp for Sentry Releases) ---
if [[ "${SKIP_SENTRY_RELEASE:-0}" != "1" ]]; then
  GIT_SHORT="$(git -C "$ROOT" rev-parse --short HEAD 2>/dev/null || echo unknown)"
  DEPLOY_TS="$(date -u +%Y-%m-%dT%H%M%SZ)"
  SENTRY_RELEASE_VAL="${GIT_SHORT}-${DEPLOY_TS}"
  if grep -q '^SENTRY_RELEASE=' .env 2>/dev/null; then
    grep -v '^SENTRY_RELEASE=' .env > .env.tmp
    mv .env.tmp .env
  fi
  printf '%s\n' "SENTRY_RELEASE=${SENTRY_RELEASE_VAL}" >> .env
  echo "==> Sentry SENTRY_RELEASE=${SENTRY_RELEASE_VAL}"
else
  echo "==> SKIP_SENTRY_RELEASE=1 — leaving .env SENTRY_RELEASE unchanged"
fi

echo "==> Ensure storage & bootstrap/cache dirs"
mkdir -p \
  storage/framework/sessions \
  storage/framework/views \
  storage/framework/cache/data \
  storage/logs \
  bootstrap/cache

echo "==> Permissions (storage, bootstrap/cache)"
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

echo "==> php artisan migrate --force"
"${ARTISAN[@]}" migrate --force

echo "==> php artisan storage:link --force"
"${ARTISAN[@]}" storage:link --force

echo "==> php artisan optimize:clear"
"${ARTISAN[@]}" optimize:clear

if [[ "${SKIP_OPTIMIZE:-0}" == "1" ]]; then
  echo "==> SKIP_OPTIMIZE=1 — skipping php artisan optimize"
else
  echo "==> php artisan optimize"
  "${ARTISAN[@]}" optimize
fi

echo ""
echo "=========================================="
echo " Done. Next:"
echo " • .env: APP_URL=https://your-domain, APP_DEBUG=false for real hosting tests"
echo " • cPanel: cron every minute → php artisan schedule:run (see comments at top of deploy.sh)"
echo " • Docroot should be the public/ folder, or use root .htaccess → public/"
echo " • Sentry: SENTRY_RELEASE in .env is refreshed each deploy (git short SHA + UTC time); set SKIP_SENTRY_RELEASE=1 to skip"
echo "=========================================="
