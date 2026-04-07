#!/usr/bin/env bash
# PHP's built-in server process must receive upload limits itself. Running
# `php -d … artisan serve` only sets ini on the parent; Laravel spawns
# `php -S` without those flags (see Illuminate\Foundation\Console\ServeCommand).
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOST="${SERVER_HOST:-127.0.0.1}"
PORT="${SERVER_PORT:-8000}"
ROUTER="$ROOT/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"

cd "$ROOT/public"

exec php \
  -d post_max_size=512M \
  -d upload_max_filesize=512M \
  -d max_execution_time=0 \
  -d max_input_time=600 \
  -S "${HOST}:${PORT}" \
  "$ROUTER"
