# AI Calorie Tracker (Kkal)

Personal calorie and weight tracker that an AI agent fills in itself. The user sends a photo or a description of food
to the agent, the agent estimates it and calls a tool on this server. The server stores the data, computes the
weight trend and the real energy expenditure (TDEE), and the web app (PWA) shows charts.

The agent side is plain MCP (Streamable HTTP), so any MCP client works: Claude (claude.ai connector, Claude Code),
or any other agent that speaks MCP. The same operations are also available as REST.

One user, no registration, one static token.

- App: https://kkal.example.com
- MCP endpoint: `https://kkal.example.com/mcp` with header `Authorization: Bearer <KKAL_TOKEN>`
  (claude.ai: Settings → Connectors → Add custom connector → No sign-in → Request headers).
  Clients without headers: `/mcp?key=<KKAL_TOKEN>`.
- Spec: `docs/spec.docx` (local only, not in git: contains personal data)

## How the numbers work

- **Meals are counted by `kcal_max`**, the upper bound of Claude's estimate (hidden oil, sauces). Errors lean towards a deficit.
- **Weight trend**: exponential moving average (alpha 0.1) of daily average weight. A gap of g days uses
  `1 - 0.9^g`, so missed days do not distort it. Talk about the trend, not single weigh-ins.
- **TDEE**, recalculated every 7 days from the first day with data (`app/Calc/EnergyModel.php`):
  - first 14 days: manual `start_tdee` or Mifflin-St Jeor (male) x activity factor. Height and age have no defaults
    (personal data stays out of the repo): until they are set in Settings, the norm is unknown;
  - after that: `avg kcal_max over logged days - (trend_end - trend_start) * 7700 / N` over the last N complete
    days (up to 28), blended `0.7 * old + 0.3 * fact`. A window with more than 30% days without food is unreliable
    and keeps the previous value. Overestimated food also raises TDEE_fact, so the two errors cancel out.
- **Norm** = TDEE x (1 - deficit %), never below `min_kcal`. Changes only on recalculation days.
- **Flags** (`GetProgressAction`): too_fast (>1 kg/week two weeks in a row), stalled (flat 3 weeks with a deficit),
  low_logging (<2 meals/day over the last 7 days), below_min.
- Everything is recomputed from history on each request. There is no stored TDEE state that could drift.
- Times are stored in UTC; a "day" is a local date in `settings.timezone` (Europe/Kyiv by default).

## Architecture

```
HTTP (REST, web app)          MCP (Claude)
  FormRequest  ──rules──┐       Tool (app/Mcp/Tools)
     │ toDto()          └────── validates with the same FormRequest::fieldRules()
     ▼                           ▼
                Dto (app/Dto)
                     ▼
            Action (app/Actions)      one use case, all logic
              │            │
     Reader (app/Readers)  Writer (app/Writers)   the only places that touch Eloquent queries
              │            │
            Models (app/Models, $fillable, enum casts)

Calc (app/Calc): pure math, no DB: WeightTrend, EnergyModel.
Services: SettingsService (settings + timezone helpers), EnergyService (daily aggregates -> trend/model).
```

Rules:
- Controllers and tools are thin: request -> DTO -> Action -> JSON. Plain lists without logic may read a Reader directly.
- Validation lives in FormRequests. MCP tools reuse `XxxRequest::fieldRules()`, so REST and MCP accept the same data.
- Partial updates (`MealUpdateDto`): null = "not changed", filtered out. Settings are the exception: null there is a
  real value ("no target weight"), so `SettingsUpdateDto` keeps exactly the keys sent.
- Enums for closed sets: `Confidence`, `MealSource`, `NoteKind`, `NoteAuthor`, `TdeeSource`.
  Metric types are an open set on purpose (steps, sleep_h, bp_sys, ...): a new metric needs no migration or enum case.
- Write actions call `EnergyService::refresh()` so reads in the same request (day totals after a write) see the change.
- The agent in chat cannot read this repo. It sees only `resources/mcp/instructions.md` (sent as MCP `instructions`)
  and the tool descriptions. Change those when the agent's behaviour should change.

## Auth

One token (`KKAL_TOKEN`) for everything, checked in `RequireToken`: `Authorization: Bearer` (preferred for MCP
clients that support request headers, e.g. the claude.ai custom connector), `X-Api-Key`, the `kkal_token` cookie
(HttpOnly, SameSite=Strict) set by `POST /api/login`, or `?key=` as a fallback for clients that take only a URL.
Constant-time compare, 401 without details, 10 failed attempts per IP per minute -> 429.
Rotate: put a new `openssl rand -hex 32` into `/opt/kkal/.env`, then `docker compose up -d --force-recreate app`.
The token must not reach logs: nginx logs `$uri` without the query string, nginx error log is `crit` only
(upstream errors would print the full request line), Caddy inside the container has no access log.

## Run locally

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate   # set KKAL_TOKEN
touch database/database.sqlite && php artisan migrate
npm run build && php artisan serve
```

Checks:

```bash
php artisan test
vendor/bin/phpstan analyse
vendor/bin/pint --test
```

Tests run on SQLite. Day grouping is done in PHP (not SQL), so the same code works on Postgres; the suite was also run
against Postgres 17 on the server with the `test` Docker target.

## Deploy

A single host with Docker and nginx; the app adds its own nginx vhost and containers. Host details
(address, ssh alias, domain, what else runs there) are kept out of git in `deploy/SERVER.local.md`.

- `/opt/kkal/compose.yml`: `app` (FrankenPHP, PHP 8.4, port 127.0.0.1:8091) + `db` (postgres:17-alpine, no published port,
  data in `/opt/kkal/pgdata`).
- `/opt/kkal/.env`: secrets (APP_KEY, KKAL_TOKEN, POSTGRES_PASSWORD). Not in the repo.
- nginx vhost `/etc/nginx/sites-available/kkal` (source: `deploy/nginx-kkal.conf`), certificate by certbot (auto-renew timer).
- Migrations run on container start (`docker/entrypoint.sh`).
- Backups: `/etc/cron.d/kkal-backup` runs `/opt/kkal/backup.sh` daily at 03:30 UTC, `pg_dump` to `/opt/kkal/backups`, 14 days kept.
  Restore: `zcat backups/kkal-<date>.sql.gz | docker compose exec -T db psql -U kkal kkal`.

```bash
deploy/deploy.sh
```

Syncs the source with rsync, rebuilds the image on the server, restarts and waits for `/up`.
