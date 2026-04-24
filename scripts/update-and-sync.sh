#!/usr/bin/env bash
set -euo pipefail

RELEASE_TAG="${1:-}"
BRANCH="${BRANCH:-main}"
SKIP_GIT="${SKIP_GIT:-false}"
SKIP_FRONTEND="${SKIP_FRONTEND:-false}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

step() {
  printf "==> %s\n" "$1"
}

env_value() {
  local key="$1"
  local default="${2:-}"
  local value

  if [[ ! -f .env ]]; then
    printf "%s" "$default"
    return
  fi

  value="$(grep -E "^${key}=" .env | head -n 1 | cut -d'=' -f2- || true)"
  value="${value%\"}"
  value="${value#\"}"

  if [[ -z "$value" ]]; then
    printf "%s" "$default"
  else
    printf "%s" "$value"
  fi
}

if [[ ! -f .env ]]; then
  echo ".env file is missing. Copy .env.example to .env before running this script." >&2
  exit 1
fi

if [[ ! -f artisan ]]; then
  echo "artisan file not found. Run this script from the BrewCloud project." >&2
  exit 1
fi

maintenance_up=false
cleanup() {
  if [[ "$maintenance_up" == true ]]; then
    step "Bringing app back online"
    "$PHP_BIN" artisan up || true
  fi
}
trap cleanup EXIT

if [[ -d .git && "$SKIP_GIT" != true ]]; then
  step "Fetching Git tags and commits"
  git fetch --all --tags

  if [[ -n "$RELEASE_TAG" ]]; then
    step "Checking out release tag $RELEASE_TAG"
    git checkout "tags/$RELEASE_TAG"
  else
    step "Pulling latest changes from origin/$BRANCH"
    git checkout "$BRANCH"
    git pull origin "$BRANCH"
  fi
fi

step "Putting app in maintenance mode"
"$PHP_BIN" artisan down
maintenance_up=true

step "Backing up central database (best effort)"
DB_CONNECTION="$(env_value DB_CONNECTION mysql)"
if [[ "$DB_CONNECTION" == "mysql" ]]; then
  DB_HOST="$(env_value DB_HOST 127.0.0.1)"
  DB_PORT="$(env_value DB_PORT 3306)"
  DB_DATABASE="$(env_value DB_DATABASE)"
  DB_USERNAME="$(env_value DB_USERNAME)"
  DB_PASSWORD="$(env_value DB_PASSWORD)"

  if [[ -n "$DB_DATABASE" && -n "$DB_USERNAME" ]]; then
    mkdir -p storage/app/backups
    BACKUP_FILE="storage/app/backups/central-$(date +%Y%m%d-%H%M%S).sql"

    if command -v mysqldump >/dev/null 2>&1; then
      if [[ -n "$DB_PASSWORD" ]]; then
        mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" || echo "Warning: mysqldump failed; continuing"
      else
        mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" "$DB_DATABASE" > "$BACKUP_FILE" || echo "Warning: mysqldump failed; continuing"
      fi
      echo "Created DB backup: $BACKUP_FILE"
    else
      echo "Warning: mysqldump not found in PATH; skipping DB backup"
    fi
  fi
fi

step "Installing PHP dependencies"
"$COMPOSER_BIN" install --no-interaction --prefer-dist --optimize-autoloader

step "Running database migrations"
"$PHP_BIN" artisan migrate --force

if [[ "$SKIP_FRONTEND" != true && -f package.json ]]; then
  step "Installing JS dependencies"
  "$NPM_BIN" ci

  step "Building frontend assets"
  "$NPM_BIN" run build
fi

step "Refreshing caches"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

step "Restarting queue workers"
"$PHP_BIN" artisan queue:restart

step "Update and sync completed"
