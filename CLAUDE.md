# CLAUDE.md

## Project

Thryds — social media platform integrating AI with humanity. Web UI + API backend. PHP 8.5, FrankenPHP, Docker.

All code implementations MUST be least invasive and straightforward.

## Rules

- ALWAYS use Docker. Never run PHP, Composer, or app tooling on the host.
- ALWAYS run `./run check:all` before completing any task.
- `./run <script>` = `docker compose exec php composer <script>` (Composer scripts only).
- For raw PHP: `docker compose exec php php scripts/<name>.php`
- Reset static state per request in worker mode (see `RequestId::reset()` in `public/index.php`).

## Commands

- `docker compose up -d` — start dev
- `./run check:all` — full checks + tests
- `./run test` — tests only
- `logs/frankenphp/access.log` — HTTP access logs (method, URI, status, duration, request ID)
- `logs/frankenphp/caddy.log` — server logs (worker restarts, file watches, Mercure)
- `logs/php/error.log` — PHP errors, warnings, deprecations
- `./run audit:production` — production readiness checks

## Custom Rector

51 rules in `utils/rector/src/`, tests in `utils/rector/tests/`, run `./run test:rector`.

Scaffold a new rule: `./run generate:rector-rule -- <RuleName> [--mode=auto|warn] [--message="..."]`