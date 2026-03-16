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
- `./run production:checklist` — run all production readiness checks (exits non-zero on failures)

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

## Production Readiness

Run `./run production:checklist` to verify the app is production ready. This exits non-zero on any failure. It checks:

1. **Route caching** — `League\Route\Cache\Router` serializes the prepared router to `var/cache/route.cache` in production, skipping route compilation on subsequent worker boots. Disabled in development so changes take effect immediately.
2. **OPcache** — ini settings, preload coverage, JIT, hit rate, memory.
3. **Template caching** — Blade compiles templates to `var/cache/blade/` and reuses them without recompilation.

Individual checks can be run separately:
- `./run route-cache:verify` — route caching only
- `./run opcache` — OPcache only
- `./run preload:generate` — regenerate preload.php locally (for dev/testing)

### Route caching

Routes are registered inside a builder callable passed to `League\Route\Cache\Router` in `public/index.php`. In production (`AppEnv::production`), the router is serialized to disk on first request and deserialized on subsequent worker boots. All route registration must happen inside this builder — never instantiate `League\Route\Router` directly (enforced by `ForbidDirectRouterInstantiationRector`).

### OPcache

`preload.php` is auto-generated at build time (`RUN php scripts/generate-preload.php` in Dockerfile). It boots the app, renders all templates, simulates a request dispatch, then uses `get_included_files()` to discover every script needed at runtime. No manual maintenance required.

Key config files:
- `docker/php/opcache.ini` — production settings (preload, no timestamps, JIT)
- `docker/php/opcache-dev.ini` — dev overrides (timestamps on, no preload)
- `preload.php` — auto-generated, preloaded into shared memory in production

## Rector Rule Patterns

Confirmed patterns learned from `LimitConstructorParamsRector` and other rules in this codebase.

### Namespace resolution for `Class_` nodes

Do NOT use `$node->getAttribute('parent')` — the `parent` attribute is unreliable during Rector traversal. Use `$class->namespacedName`, which PhpParser's `NameResolver` visitor populates automatically before Rector runs:

```php
private function resolveNamespace(Class_ $class): ?string
{
    if ($class->namespacedName !== null) {
        $parts = $class->namespacedName->getParts();
        if (count($parts) > 1) {
            array_pop($parts);
            return implode('\\', $parts);
        }
    }
    return null;
}
```

### Accessing the current file path

`AbstractRector` exposes a `protected` property `$this->file` of type `Rector\ValueObject\Application\File`. Use `$this->file->getFilePath()` to get the absolute path of the file being processed. Useful for rules that write new files alongside the source (e.g. generating a DTO next to the class that uses it).

### Extract count vs excess count

When extracting N params into a new DTO class, the DTO param itself occupies one slot in the remaining list. The formula is:

```
extractCount = excessCount + 1
```

Example: 6 params, maxParams=4 → excess=2, but extract 3 so that 3 remaining + 1 DTO param = 4.

### Test config: `dtoOutputDir` with `sys_get_temp_dir()`

Rules that generate files as a side effect should accept a configurable output directory. Test configs should point to `sys_get_temp_dir()` so generated files do not accumulate inside the test directory:

```php
$rectorConfig->ruleWithConfiguration(MyRule::class, [
    'outputDir' => sys_get_temp_dir(),
]);
```

The fixture file only validates AST transformations; the generated file is a side effect verified separately.

### Co-occurrence grouping pitfall

Grouping params by which methods use them together (co-occurrence) can backfire: "core" dependencies that are used everywhere score highest and end up selected for extraction, which is the opposite of the desired outcome. Prefer type-name prefix grouping first; fall back to positional selection (last N params) rather than co-occurrence when a semantic grouping cannot be found.

### `importNames()` and `FullyQualified` nodes

`rector.php` enables `$rectorConfig->importNames()`. When rules create nodes with `FullyQualified` types, Rector automatically adds the corresponding `use` statements. There is no need to manually insert imports in rule code.

### Do not use `Webmozart\Assert\Assert`

Rector scopes it under a version-prefixed namespace (e.g. `RectorPrefix202603\Webmozart\Assert\Assert`) internally. Importing it from custom rules will fail at runtime. Perform input validation with plain PHP conditionals instead.

## Non-negotiable Design Decisions
1.