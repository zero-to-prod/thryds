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

Fallback when the dev server is not running (slower, starts a new container):
- `docker compose run --rm composer sh` — run a shell inside a container
- `docker compose run --rm composer composer <command>` — run any Composer command

## Logs
- logs/frankenphp/caddy.log

## Rules

- ALWAYS use Docker to interact with the app. Never run PHP, Composer, or any app tooling directly on the host.

## Error Handling

Error handling lives in `public/index.php` as a try/catch around `$Router->dispatch()`.

- **HTTP errors** (404, 405, etc.): League\Route throws `League\Route\Http\Exception` subclasses. These are caught and rendered via `templates/error.blade.php` with the correct status code.
- **Unexpected errors**: Caught as `\Throwable`. Logged via `Log::error()` with exception context. In production, the user sees a generic "Internal Server Error". In development, the actual message is shown.
- **Never** use `try/catch` inside route handlers to suppress errors. Let exceptions propagate to the top-level handler.
- **Never** use `error_log()` or `frankenphp_log()` directly. Use `Log::error()`, `Log::warn()`, `Log::info()`, or `Log::debug()`.

### Throwing HTTP errors in route handlers

Use League\Route's built-in HTTP exceptions to signal error responses:
```php
use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception\BadRequestException;
use League\Route\Http\Exception\UnauthorizedException;
use League\Route\Http\Exception\ForbiddenException;
use League\Route\Http\Exception\UnprocessableEntityException;

// In a route handler:
throw new NotFoundException('User not found');
throw new BadRequestException('Missing required field: email');
```

### API routes (JSON responses)

When adding API routes, use League\Route's `JsonStrategy` on a route group. It automatically returns JSON error responses for HTTP exceptions:
```php
use Laminas\Diactoros\ResponseFactory;
use League\Route\Strategy\JsonStrategy;

$JsonStrategy = new JsonStrategy(responseFactory: new ResponseFactory());
$Router->group('/api', function ($RouteGroup) {
    $RouteGroup->map('GET', '/users', $handler);
})->setStrategy(strategy: $JsonStrategy);
```
API route handlers can return arrays or `JsonSerializable` objects directly — `JsonStrategy` encodes them automatically.

## Request Validation

- Validate at the boundary: inside route handlers, before any business logic.
- For invalid input, throw `BadRequestException` (malformed) or `UnprocessableEntityException` (well-formed but semantically invalid).
- Do not create validation middleware or framework abstractions until there are enough routes to justify it.

## Response Formatting

- **Web routes**: Return `HtmlResponse` with a Blade-rendered template.
- **API routes**: Return arrays, `JsonSerializable` objects, or `JsonResponse` from handlers using `JsonStrategy`.
- **Error pages**: Rendered via `templates/error.blade.php` (receives `status_code` and `message`).

## Documentation Repos

`docs.sh` manages cloned GitHub repos in `docs/repos/` for offline reference.

- Composer packages: automatically resolved from `composer.json` + `composer.lock` source URLs.
- Arbitrary repos: added to the `EXTRA_REPOS` array in `docs.sh`.
- `./docs.sh install` — clones missing repos (`--depth 1`), skips already cloned.
- `./docs.sh update` — pulls latest for all cloned repos (`--ff-only`).

## Non-negotiable Design Decisions
1.