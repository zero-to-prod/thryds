# CLAUDE.md

## Project

Thryds is a social media side designed to integrate AI with humanity.

It does this via 2 main features: a web UI and an api backend.

## Technologies

- Git
  - .gitignore
  - docs: docs/repos/git/htmldocs
- PHP 8.5
  - docs: 
    - docs/repos/php/doc-en/language
    - docs/repos/php/doc-en/reference
- Composer
  - composer.json
  - docs: docs/repos/composer/composer/doc
- Docker
  - docs: docs/repos/docker/docs
  - ./compose.yaml
  - Dockerfile
- FrankenPHP: (Caddy-based PHP app server) in Docker, PHP 8.5
  - logs/frankenphp/caddy.log
  - docs: docs/repos/php/frankenphp/docs
- Rector: (PHP code refactoring tool) in Docker
  - rector.php
  - docs: docs/repos/rectorphp/rector/stubs-rector, templates, rules, config
- PHP CS Fixer: (PHP code style enforcer) in Docker
  - .php-cs-fixer.php
  - docs: docs/repos/PHP-CS-Fixer/PHP-CS-Fixer/doc
- PhpUnit: (PHP unit testing framework) in Docker
  - phpunit.xml.dist
  - docs: docs/repos/sebastianbergmann
- Twig: (PHP template engine) in Docker
  - docs: docs/repos/twigphp/Twig/doc

## Commands

- `sh update-docs.sh` - update docs/repos/
- `docker compose up -d` — start dev server

### Running commands in Docker

The `./run` script wraps `docker compose exec php composer` (requires dev server running):
- `./run <command>` — run any Composer command
- `./run test` — run all tests
- `./run test:unit` — run unit tests
- `./run test:integration` — run integration tests
- `./run test:rector` — run Rector rule tests
- `./run lint` — fix code style
- `./run lint:check` — preview code style changes
- `./run rector` — apply Rector changes
- `./run rector:check` — preview Rector changes

Fallback when the dev server is not running (slower, starts a new container):
- `docker compose run --rm composer sh` — run a shell inside a container
- `docker compose run --rm composer composer <command>` — run any Composer command

## Logs
- logs/frankenphp/caddy.log

## Rules

- ALWAYS use Docker to interact with the app. Never run PHP, Composer, or any app tooling directly on the host.

## Non-negotiable Design Decisions
1.