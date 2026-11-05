#!/bin/sh
# Daily Postgres dump, kept 14 days. Installed as /opt/kkal/backup.sh, run from /etc/cron.d/kkal-backup.
set -eu
cd /opt/kkal
mkdir -p backups
f="backups/kkal-$(date +%F).sql.gz"
docker compose exec -T db pg_dump -U kkal --clean --if-exists kkal | gzip > "$f.tmp"
mv "$f.tmp" "$f"
find backups -name 'kkal-*.sql.gz' -mtime +14 -delete
