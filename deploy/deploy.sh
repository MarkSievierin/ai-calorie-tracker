#!/usr/bin/env bash
# Deploy from this checkout to the server: sync source, rebuild, restart.
# Usage: deploy/deploy.sh [ssh-host]   (default: kkal, see ~/.ssh/config)
set -euo pipefail
HOST="${1:-kkal}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

rsync -az --delete \
  --exclude .git --exclude node_modules --exclude vendor --exclude .env --exclude 'storage/logs/*' \
  --exclude storage/shots --exclude 'storage/framework/*/*' --exclude public/build --exclude public/hot \
  --exclude database/database.sqlite --exclude 'deploy/*.local' --exclude docs/spec.docx \
  "$ROOT/" "$HOST:/opt/kkal/app/"

ssh "$HOST" 'set -e
  cd /opt/kkal
  install -m 644 app/deploy/compose.yml compose.yml
  install -m 755 app/deploy/backup.sh backup.sh
  docker compose build app
  docker compose up -d --remove-orphans
  for i in $(seq 1 30); do
    if curl -fsS -o /dev/null http://127.0.0.1:8091/up; then echo "up"; exit 0; fi
    sleep 2
  done
  docker compose logs --tail 50 app
  exit 1'
