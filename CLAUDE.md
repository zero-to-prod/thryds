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
- Blade: (Laravel Blade template engine, standalone) in Docker
    - docs/repos/jenssegers/blade
    - docs/repos/laravel/docs/blade.md

## Commands

- `./docs.sh install` — clone docs/repos/ (skips already cloned)
- `./docs.sh update` — pull latest for docs/repos/
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
- `./run opcache` — audit OPcache config (exits non-zero on failures)
- `./run preload:generate` — regenerate preload.php from the worker's runtime script list
- `./run route-cache:verify` — verify route caching works in production mode
- `./run lint:blade-routes` — check Blade templates for hardcoded route paths
- `./run lint:all` — **run this after every change** — fixes code style, applies Rector, checks Blade routes, runs tests
- `./run production:checklist` — run all production readiness checks (exits non-zero on failures)

Fallback when the dev server is not running (slower, starts a new container):

- `docker compose run --rm composer sh` — run a shell inside a container
- `docker compose run --rm composer composer <command>` — run any Composer command

## Logs

- logs/frankenphp/caddy.log

## Rules

- ALWAYS use Docker to interact with the app. Never run PHP, Composer, or any app tooling directly on the host.
- ALWAYS run **`./run lint:all` — **run before completing** — fixes code style, applies Rector, checks Blade routes, runs tests**

## Production Readiness

Run `./run production:checklist` to verify the app is production ready. This exits non-zero on any failure. It checks:

1. **Route caching** — `League\Route\Cache\Router` serializes the prepared router to `var/cache/route.cache` in production, skipping route compilation on subsequent worker boots. Disabled in development so changes take effect immediately.
2. **OPcache** — ini settings, preload coverage, JIT, hit rate, memory.
3. **Template caching** — Blade compiles templates to `var/cache/blade/` and reuses them without recompilation.

### Route caching

Routes are registered inside a builder callable passed to `League\Route\Cache\Router` in `public/index.php`. In production (`AppEnv::production`), the router is serialized to disk on first request and deserialized on subsequent worker boots. All route registration must happen inside this builder — never instantiate `League\Route\Router` directly (enforced by `ForbidDirectRouterInstantiationRector`).

### OPcache

`preload.php` is auto-generated at build time (`RUN php scripts/generate-preload.php` in Dockerfile). It boots the app, renders all templates, simulates a request dispatch, then uses `get_included_files()` to discover every script needed at runtime. No manual maintenance required.

Key config files:

- `docker/php/opcache.ini` — production settings (preload, no timestamps, JIT)
- `docker/php/opcache-dev.ini` — dev overrides (timestamps on, no preload)
- `preload.php` — auto-generated, preloaded into shared memory in production