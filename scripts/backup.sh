#!/usr/bin/env bash
#
# Reproducible backup of PostIt's irreplaceable data (see docs/logging_backup_policy.md).
# Backs up: the database and the uploaded files (storage/app). It does NOT back up
# data that can be regenerated from source (vendor/, node_modules/, caches, built assets).
# Produces a timestamped, compressed archive plus a SHA-256 checksum for integrity.
#
# Usage:  scripts/backup.sh [BACKUP_DIR]
#         BACKUP_DIR defaults to ./backups
#
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${1:-$APP_ROOT/backups}"
STAMP="$(date -u +%Y%m%d_%H%M%SZ)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

mkdir -p "$BACKUP_DIR"
echo "PostIt backup $STAMP (UTC)"

# --- read DB settings from .env (fallback to sqlite) ---
env_get() { grep -E "^$1=" "$APP_ROOT/.env" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"' || true; }
DB_CONNECTION="$(env_get DB_CONNECTION)"; DB_CONNECTION="${DB_CONNECTION:-sqlite}"

mkdir -p "$WORK/db" "$WORK/files"

# --- database dump ---
case "$DB_CONNECTION" in
  sqlite)
    DB_PATH="$(env_get DB_DATABASE)"; DB_PATH="${DB_PATH:-$APP_ROOT/database/database.sqlite}"
    if [ -f "$DB_PATH" ]; then
      if command -v sqlite3 >/dev/null 2>&1; then
        # .backup gives a consistent copy even while the app is running
        sqlite3 "$DB_PATH" ".backup '$WORK/db/database.sqlite'"
      else
        # fallback: plain copy (use when the sqlite3 CLI is unavailable)
        cp "$DB_PATH" "$WORK/db/database.sqlite"
      fi
      echo "  database (sqlite): dumped"
    else
      echo "  database (sqlite): file not found at $DB_PATH" >&2; exit 1
    fi ;;
  mysql)
    mysqldump --single-transaction --routines --triggers \
      -h "$(env_get DB_HOST)" -P "$(env_get DB_PORT)" \
      -u "$(env_get DB_USERNAME)" -p"$(env_get DB_PASSWORD)" \
      "$(env_get DB_DATABASE)" > "$WORK/db/database.sql"
    echo "  database (mysql): dumped" ;;
  *) echo "  unsupported DB_CONNECTION: $DB_CONNECTION" >&2; exit 1 ;;
esac

# --- uploaded files (user content that cannot be regenerated) ---
if [ -d "$APP_ROOT/storage/app" ]; then
  tar -cf "$WORK/files/storage_app.tar" -C "$APP_ROOT/storage" app
  echo "  storage/app: archived"
fi

# --- manifest ---
cat > "$WORK/MANIFEST.txt" <<EOF
PostIt backup
created_utc: $STAMP
db_connection: $DB_CONNECTION
includes: database, storage/app
excludes: vendor, node_modules, caches, built assets (regenerable from source)
EOF

# --- single archive + integrity checksum ---
ARCHIVE="$BACKUP_DIR/postit_backup_$STAMP.tar.gz"
tar -czf "$ARCHIVE" -C "$WORK" .
( cd "$BACKUP_DIR" && sha256sum "$(basename "$ARCHIVE")" > "$(basename "$ARCHIVE").sha256" )

echo "Backup written: $ARCHIVE"
echo "Checksum:       $ARCHIVE.sha256"
