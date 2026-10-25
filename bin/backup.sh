#!/bin/bash
# Daily backup of the database and the uploaded files (Step 7c-2). Run by a cron job (see docs/DEPLOY.md, "Backups").
#
#   db-<date>.sql.gz      the whole MySQL database
#   files-<date>.tar.gz   storage/app/private (CNIC, degrees) and storage/app/public (photos)
#   latest.txt            names of the newest pair (the copy-to-PC script reads it)
#
# Goes to ~/backups (outside the web root). Keeps the newest 7 pairs. The password is read from .env and handed
# to mysqldump through the environment, so it never shows on a command line or in any log. .env itself is NOT in
# the backup: keep your own copy of it somewhere safe.
set -euo pipefail
umask 077
export PATH=/usr/local/bin:/usr/bin:/bin

APP="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${BACKUP_DIR:-$HOME/backups}"
KEEP="${BACKUP_KEEP:-7}"
stamp="$(date +%Y-%m-%d_%H%M)"
db_file="$OUT/db-$stamp.sql.gz"
files_file="$OUT/files-$stamp.tar.gz"

mkdir -p "$OUT"
say() { printf '%s %s\n' "$(date '+%F %T')" "$*" | tee -a "$OUT/backup.log"; }
fail() { say "FAILED: $*"; rm -f "$db_file" "$files_file"; exit 1; }
env_value() { grep -E "^$1=" "$APP/.env" | head -1 | cut -d= -f2- | sed "s/^[\"']//; s/[\"']\$//"; }

[ -f "$APP/.env" ] || fail "no .env in $APP"
DB_HOST="$(env_value DB_HOST)"; DB_PORT="$(env_value DB_PORT)"; DB_NAME="$(env_value DB_DATABASE)"
DB_USER="$(env_value DB_USERNAME)"; DB_PASS="$(env_value DB_PASSWORD)"
[ -n "$DB_NAME" ] && [ -n "$DB_USER" ] || fail ".env has no database name or user"

# 1. database
MYSQL_PWD="$DB_PASS" mysqldump -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USER" \
    --single-transaction --no-tablespaces "$DB_NAME" | gzip -9 > "$db_file" || fail "mysqldump failed"
gzip -t "$db_file" || fail "the database backup is damaged"
[ "$(wc -c < "$db_file")" -gt 2000 ] || fail "the database backup is suspiciously small"
gzip -dc "$db_file" | grep -q "CREATE TABLE" || fail "the database backup has no tables"

# 2. uploaded files
tar -czf "$files_file" -C "$APP/storage/app" private public || fail "tar failed"
tar -tzf "$files_file" > /dev/null || fail "the files backup is damaged"

# 3. only now: mark as latest and delete the oldest (keep the newest $KEEP of each kind)
printf '%s\n%s\n' "$(basename "$db_file")" "$(basename "$files_file")" > "$OUT/latest.txt"
for kind in db files; do
    ls -1t "$OUT"/"$kind"-*.gz 2>/dev/null | tail -n +"$((KEEP + 1))" | xargs -r rm -f
done

say "OK  $(basename "$db_file") $(du -h "$db_file" | cut -f1), $(basename "$files_file") $(du -h "$files_file" | cut -f1); kept $(ls -1 "$OUT"/db-*.gz | wc -l) backups"
