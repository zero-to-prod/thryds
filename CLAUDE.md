# CLAUDE.md

## Project

Thryds — social media platform integrating AI with humanity. Web UI + API backend. PHP 8.5, FrankenPHP, Docker.

All code implementations MUST be least invasive and straightforward.

## Rules

- ALWAYS use Docker. Never run PHP, Composer, or app tooling on the host.
- ALWAYS run `./run check:all` before completing any task.
- `./run <script>` = `docker compose exec web composer <script>` for all Composer scripts, or `docker compose -f compose.load-test.yaml run --rm k6` for `test:load`, or the named aliases below.
- For raw PHP: `docker compose exec web php scripts/<name>.php`
- Reset static state per request in worker mode (see `RequestId::reset()` in `public/index.php`).

## Docker Compose Files

| File | Purpose |
|---|---|
| `compose.yaml` | Base config (dev + prod). Always loaded. |
| `compose.development.yaml` | Dev overrides — must be passed explicitly with `-f`. Adds hot_reload + file-watching worker. Never applies to production. |
| `compose.load-test.yaml` | Production load test. Builds `production` target, skips the override entirely. |

Dev: `./run dev:up`
Production: `APP_ENV=production docker compose -f compose.yaml up -d`

## Commands

- `./run dev:up` — start dev
- `./run check:all` — full checks + tests
- `./run test` — tests only
- `./run test:load` — run k6 load test against a production build (results → `logs/k6/results.json`)
- `logs/frankenphp/access.log` — HTTP access logs (method, URI, status, duration, request ID)
- `logs/frankenphp/caddy.log` — server logs (worker restarts, file watches, Mercure)
- `logs/php/error.log` — PHP errors, warnings, deprecations
- `./run audit:production` — production readiness checks

## Custom Rector

69 rules in `utils/rector/src/` (verify with `ls utils/rector/src/ | wc -l`), tests in `utils/rector/tests/`, run `./run test:rector`.

Scaffold a new rule: `./run generate:rector-rule -- <RuleName> [--mode=auto|warn] [--message="..."]`

## Organizing Principles

- Constants name things
- Enumerations define sets
- Php Attributes define properties