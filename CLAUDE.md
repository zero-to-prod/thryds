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
- `docker compose run --rm composer`: run Composer commands inside the app container
  - `sh` — run a shell inside the app container
  - `./vendor/bin/test` — run tests
  - `composer <command>` — run any Composer command (e.g. `update`, `require`, `install`)
  - `./vendor/bin/phpunit utils` - run PHPUnit tests in the `utils` directory like rector
  - `./vendor/bin/php-cs-fixer fix` — fix code style
  - `./vendor/bin/php-cs-fixer fix --dry-run --diff` — preview code style changes
  - `./vendor/bin/rector process src --dry-run` — preview Rector changes
  - `./vendor/bin/rector process src` — apply Rector changes

## Logs
- logs/frankenphp/caddy.log

## Rules

- ALWAYS use Docker to interact with the app. Never run PHP, Composer, or any app tooling directly on the host.

## Non-negotiable Design Decisions
1.