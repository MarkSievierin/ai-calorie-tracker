# Kkal: notes for Claude Code

Personal project of Mark. Not related to AERT/nap work: no ClickUp, GitLab, MR flow or daily reports.
Chat with Mark in Russian; code, comments and docs in English.

Read `README.md` first: domain (kcal_max, trend, adaptive TDEE), architecture rules (FormRequest -> Dto -> Action ->
Reader/Writer), auth and deploy.

- Stack: Laravel 13, PHP 8.4 (composer platform pinned to the server's 8.4.25; local PHP may be newer), laravel/mcp,
  Alpine.js + Chart.js + Tailwind 4 via Vite, Postgres 17 in prod, SQLite locally and in tests.
- Node 22 via nvm for the frontend build (`source ~/.nvm/nvm.sh && nvm use 22`).
- Before handing off: `php artisan test`, `vendor/bin/phpstan analyse`, `vendor/bin/pint --test`, `npm run build`.
- Deploy: `deploy/deploy.sh` (needs ssh alias `kkal`). Host details: `deploy/SERVER.local.md` (local only, never commit
  server address or domain). Production data is real: do not seed or wipe it.
- Behaviour of Claude in chat = `resources/mcp/instructions.md` + tool descriptions in `app/Mcp/Tools`.
- Update README when logic or architecture changes, with the reason.
