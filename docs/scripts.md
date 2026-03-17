# Scripts Reference

## `./run` (Project Root)

Wrapper that executes Composer scripts inside Docker: `docker compose exec php composer "$@"`.

Usage: `./run <script-name>`

---

## Composer Scripts

Invoked via `./run <name>`. Defined in `composer.json`.

### Checking (read-only)

| Script | Command | Description |
|--------|---------|-------------|
| `check:style` | `php-cs-fixer fix --dry-run --diff` | Check code style without applying changes |
| `check:rector` | `rector process --dry-run` | Preview Rector changes without applying |
| `check:types` | `phpstan analyse` | Run static analysis (level 2) |
| `check:blade-routes` | `php scripts/lint-blade-routes.php` | Detect hardcoded route paths in Blade templates |
| `check:route-cache` | `php scripts/verify-route-cache.php` | Verify route caching is working correctly |
| `check:all` | Runs: fix:style, fix:rector, check:types, check:blade-routes, test, generate:preload | Full check + test suite — run after every change |

### Fixing (writes changes)

| Script | Command | Description |
|--------|---------|-------------|
| `fix:style` | `php-cs-fixer fix` | Apply PHP CS Fixer code style fixes |
| `fix:rector` | `rector process` | Apply Rector refactorings |

### Testing

| Script | Command | Description |
|--------|---------|-------------|
| `test` | `phpunit` | Run all tests |
| `test:unit` | `phpunit --testsuite unit` | Run unit tests only |
| `test:integration` | `phpunit --testsuite integration` | Run integration tests only |
| `test:rector` | `phpunit utils` | Run custom Rector rule tests |

### Generating

| Script | Command | Description |
|--------|---------|-------------|
| `generate:preload` | `php scripts/generate-preload.php` | Generate `preload.php` for OPcache preloading |
| `generate:rector-rule` | `php scripts/make-rector-rule.php` | Scaffold a new custom Rector rule |

### Auditing

| Script | Command | Description |
|--------|---------|-------------|
| `audit:opcache` | `php scripts/opcache-audit.php` | Audit OPcache configuration and performance |
| `audit:production` | `php scripts/production-checklist.php` | Run all production readiness checks |

---

## PHP Scripts (`scripts/`)

Run directly via `docker compose exec php php scripts/<name>.php`, or through their Composer aliases above.

### `generate-preload.php`

Generates `preload.php` by booting the app, rendering all templates, and discovering loaded files via `get_included_files()`. Performs topological sorting to order classes by dependency. Filters out dev-only files (tests, utils, dev vendor packages). Output is organized into groups: Autoload, Helpers, Core, Routes, ViewModels, Entrypoint, Vendor.

### `verify-route-cache.php`

Verifies `League\Route\Cache\Router` caching behavior. Tests that in production mode the route builder runs once and subsequent dispatches load from cache. Tests that in development mode the builder runs every time with no cache file written. Runs 5 assertions total.

### `lint-blade-routes.php`

Scans all `.blade.php` files for hardcoded route paths (e.g., `href="/about"`, `{{ '/login' }}`). Allows fragment-only links (`#anchor`), external URLs, and Route enum references. Exits non-zero if violations are found.

### `opcache-audit.php`

Comprehensive OPcache audit with 11 checks: OPcache enabled, preload configured, timestamp validation off in production, JIT enabled with adequate buffer, sufficient `max_accelerated_files`, cache hit rate, cached script coverage, memory usage/fragmentation, and flag settings (`save_comments`, `enable_file_override`). Generates warm-up traffic to measure delta-based hit rates. Skips production-only checks in dev mode.

### `production-checklist.php`

Master checklist that runs three sub-checks: route cache verification, OPcache audit, and template cache verification. Template cache check renders all views, verifies compiled `.php` files are created, then re-renders to confirm mtimes are unchanged (no unnecessary recompilation). Exits non-zero if any check fails.

---

## Shell Scripts

### `scripts/opcache-load-test.sh`

Load tests the app and measures OPcache performance using Apache Bench (`ab`).

| Parameter | Default | Description |
|-----------|---------|-------------|
| `$1` | 500 | Number of requests |
| `$2` | 10 | Concurrency level |
| `$3` | 8888 | Server port |

Warms up cache by hitting every route once, runs the load test, then reports requests/sec, time per request, transfer rate, and failed requests. Fetches OPcache metrics from `/_opcache` endpoint afterward.

### `docs/docs.sh`

Clones and updates documentation repositories for all direct dependencies.

| Command | Description |
|---------|-------------|
| `./docs/docs.sh install` | Shallow-clone all dependency doc repos into `docs/repos/{org}/{repo}/` |
| `./docs/docs.sh update` | Pull latest changes for already-cloned repos |

Extracts GitHub repos from `composer.lock` and queries the npm registry for npm packages. Includes hardcoded extra repos (e.g., `laravel/docs`).