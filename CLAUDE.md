# CLAUDE.md

## Project

Thryds — social media platform integrating AI with humanity. Web UI + API backend. PHP 8.5, FrankenPHP, Docker.

## Rules

- ALWAYS use Docker. Never run PHP, Composer, or app tooling on the host.
- ALWAYS run `./run lint:all` before completing any task.
- `./run` wraps `docker compose exec php composer` — Composer scripts only, not arbitrary PHP.
- For non-Composer PHP scripts: `docker compose exec php php scripts/<script>.php`

## Quick Reference

- `docker compose up -d` — start dev server
- `./run lint:all` — lint + rector + phpstan + blade route check + tests (run after every change)
- `./run test` — all tests
- `./run lint` — fix code style
- `./run rector` — apply Rector refactors
- `./run phpstan` — static analysis (catches undefined classes, bad types)
- `logs/frankenphp/caddy.log` — server logs
- `docker compose exec php php scripts/production-checklist.php` — run all production readiness checks (exits non-zero on failures)
    - Change .env to production and rebuild and restart the app to see production-ready checks.

## Naming Conventions (Rector-enforced)

- Object instances: PascalCase (`$Config`, `$Blade`, `$Router`) — matches their type name.
- Primitive variables and properties: snake_case (`$base_dir`, `$cache_dir`).
- Enum cases: must match their string value exactly (e.g., `case production = 'production'`).

## PHPStan

- Level is set to 2. ext-frankenphp stubs cause false positives at higher levels. Do not raise the level without testing the full build.

## FrankenPHP Worker Model

- PHP process state persists across requests. Any static mutable state must be reset after each request. See `RequestId::reset()` in `public/index.php` as the canonical example.
- `frankenphp_handle_request()`, `FRANKENPHP_LOG_LEVEL_*` constants, and `frankenphp_log()` are provided by ext-frankenphp at runtime and do not appear in source.
