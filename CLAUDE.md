# CLAUDE.md

## Project

Thryds is a social media side designed to integrate AI with humanity.

It does this via 2 main features: a web UI and an api backend.

## Technologies

- Docker
  - docs: docs/repos/docker/docs
  - ./compose.yaml
- FrankenPHP: (Caddy-based PHP app server) in Docker, PHP 8.5
  - docs: docs/repos/php/frankenphp/docs
- Rector: (PHP code refactoring tool) in Docker
  - docs: docs/repos/rectorphp/rector/stubs-rector, templates, rules, config
- PhpUnit: (PHP unit testing framework) in Docker
  - docs: docs/repos/sebastianbergmann

## Commands

- `docker compose up -d` — start dev server
- `docker compose run --rm composer`: run Composer commands inside the app container
  - `sh` — run a shell inside the app container
  - `./vendor/bin/test` — run tests
  - `composer <command>` — run any Composer command (e.g. `update`, `require`, `install`)
  - `./vendor/bin/phpunit utils` - run PHPUnit tests in the `utils` directory like rector

## Logs
- logs/frankenphp/caddy.log

## Rules

- ALWAYS use Docker to interact with the app. Never run PHP, Composer, or any app tooling directly on the host.

## Non-negotiable Design Decisions
1.