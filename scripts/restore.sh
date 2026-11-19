#!/usr/bin/env bash
#
# Restore PostIt from a backup archive produced by backup.sh, after verifying
# its SHA-256 integrity (see docs/logging_backup_policy.md).
#
# Usage:  scripts/restore.sh BACKUP_ARCHIVE.tar.gz
#
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ARCHIVE="${1:?usage: restore.sh BACKUP_ARCHIVE.tar.gz}"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

# --- 1. verify integrity before touching anything ---
if [ -f "$ARCHIVE.sha256" ]; then
  ( cd "$(dirname "$ARCHIVE")" && sha256sum -c "$(basename "$ARCHIVE").sha256" )
  echo "Integrity check: OK"
else
  echo "No .sha256 checksum next to $ARCHIVE — refusing to restore unverified data." >&2
  exit 1
fi

tar -xzf "$ARCHIVE" -C "$WORK"
echo "Archive contents:"; cat "$WORK/MANIFEST.txt" | sed 's/^/  /'

env_get() { grep -E "^$1=" "$APP_ROOT/.env" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"' || true; }
DB_CONNECTION="$(env_get DB_CONNECTION)"; DB_CONNECTION="${DB_CONNECTION:-sqlite}"

# --- 2. restore the database ---
case "$DB_CONNECTION" in
  sqlite)
    DB_PATH="$(env_get DB_DATABASE)"; DB_PATH="${DB_PATH:-$APP_ROOT/database/database.sqlite}"
    cp "$WORK/db/database.sqlite" "$DB_PATH"
    echo "  database (sqlite): restored to $DB_PATH" ;;
  mysql)
    mysql -h "$(env_get DB_HOST)" -P "$(env_get DB_PORT)" \
      -u "$(env_get DB_USERNAME)" -p"$(env_get DB_PASSWORD)" \
      "$(env_get DB_DATABASE)" < "$WORK/db/database.sql"
    echo "  database (mysql): restored" ;;
esac

# --- 3. restore uploaded files ---
if [ -f "$WORK/files/storage_app.tar" ]; then
  tar -xf "$WORK/files/storage_app.tar" -C "$APP_ROOT/storage"
  echo "  storage/app: restored"
fi

echo "Restore complete. Verify the application (auth, data access) before returning to service."
