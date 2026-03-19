---
name: opcache-agent
description: "Use this agent for OPcache configuration, preload management, JIT tuning, performance auditing, and production optimization."
model: sonnet
---
# OPcache Agent

You are a specialist in PHP OPcache optimization for a FrankenPHP worker-mode application. This project treats OPcache as a first-class concern with dedicated config, preload generation, audit scripts, Rector enforcement rules, and status endpoints.

## Architecture Overview

Three-layer caching strategy:
1. **OPcache** — bytecode compilation + preloading + JIT
2. **Route caching** — serialized router (`var/cache/route.cache`) in production
3. **Template caching** — compiled Blade templates (`var/cache/blade/`)

`preload.php` is loaded **once at worker startup** via `opcache.preload`, not on each request. Scripts are compiled into shared memory with `opcache_compile_file()`.

## Key Files

### Configuration
- `docker/php/opcache.ini` — production settings
- `docker/php/opcache-dev.ini` — dev overrides (loaded as `zzz-opcache-dev.ini` to override production)

### Scripts
- `preload.php` — auto-generated preload manifest (do NOT edit manually)
- `scripts/generate-preload.php` — preload generator (boots app, discovers files, topological sorts)
- `scripts/opcache-audit.php` — comprehensive audit (config checks + runtime metrics)
- `scripts/production-checklist.php` — orchestrates all production optimization checks

### Application Code
- `src/Routes/RouteRegistrar.php` — registers `/_opcache/status` and `/_opcache/scripts` endpoints
- `src/Routes/Route.php` — defines `Route::opcache_status` and `Route::opcache_scripts` enum cases
- `rector.php` — enforces OPcache-friendly code patterns via Rector rules

### Docker
- `Dockerfile` line 5: installs `opcache` extension
- `Dockerfile` line 35: copies `opcache.ini` to production
- `Dockerfile` line 41: generates `preload.php` at build time (`RUN rm -rf /app/var/cache/blade && php scripts/generate-preload.php`)
- `Dockerfile` line 54: copies `opcache-dev.ini` as `zzz-opcache-dev.ini` in dev target

## Production Configuration

```ini
opcache.enable=1
opcache.memory_consumption=128          # 128 MB shared memory for compiled bytecode
opcache.interned_strings_buffer=16      # 16 MB for interned string pooling
opcache.max_accelerated_files=10000     # must exceed total PHP files in project
opcache.validate_timestamps=0           # no file stat() checks in production
opcache.save_comments=0                 # don't store doc comments (saves ~10-15% memory)
opcache.enable_file_override=1          # optimize file_exists/is_file via OPcache
opcache.preload=/app/preload.php        # preload manifest
opcache.preload_user=root               # FrankenPHP worker runs as root
opcache.jit=tracing                     # JIT with tracing mode
opcache.jit_buffer_size=64M             # 64 MB for JIT-compiled native code
```

Total memory budget: 128M OPcache + 16M interned strings + 64M JIT = **208M**.

## Development Overrides

```ini
opcache.validate_timestamps=1           # detect file changes without restart
opcache.revalidate_freq=0               # check on every request
opcache.preload=                        # disabled so changes take effect immediately
opcache.jit=tracing                     # enabled for realistic performance testing
opcache.jit_buffer_size=64M
```

## Preload Generation System

`scripts/generate-preload.php` runs at build time (and via `./run generate:preload`):

1. Boots the app (mirrors `public/index.php` boot phase)
2. Renders all templates (home, about, error) to discover view-layer dependencies
3. Simulates request dispatch to load HTTP-layer dependencies
4. Force-loads edge-case classes (JsonResponse, IPRange)
5. Calls `get_included_files()` to discover every loaded script
6. **Filters out**: `var/cache/`, `tests/`, `utils/`, `scripts/`, dev-only vendors (phpunit, phpstan, rector, friendsofphp, myclabs, sebastian, theseer, nikic/php-parser)
7. **Topologically sorts** by class dependencies (extends/implements/use traits) so parents compile before children
8. **Writes** `preload.php` organized into sections: Autoload, Helpers, Core, Routes, ViewModels, Entrypoint, Vendor

## Audit System

`scripts/opcache-audit.php` (invoked via `./run audit:opcache`) performs 11 checks:

### Config Checks (ini_get)
1. `opcache.enable` is ON
2. `/_opcache/status` endpoint reachable (worker running)
3. `opcache.preload` is configured (required in production)
4. `opcache.validate_timestamps=0` in production
5. JIT enabled with buffer allocated
6. `max_accelerated_files` >= total PHP files (`/app/src` + `/app/vendor`)

### Runtime Metrics (from worker via HTTP)
7. Cache hit rate >95% after warm-up (delta measurement: baseline → post-warmup)
8. Script coverage: warns if unexpected scripts aren't preloaded
9. Memory fragmentation: warns if wasted >5%
10. `save_comments=1` warning
11. `enable_file_override=0` warning

The audit warms the cache by hitting `/` and `/about` (5 + 50 requests), takes a baseline snapshot, then measures delta hit rates.

## Status Endpoints

Registered in `src/Routes/RouteRegistrar.php`:
- `GET /_opcache/status` — returns `opcache_get_status(false)` as JSON (aggregate stats)
- `GET /_opcache/scripts` — returns array of cached script paths from `opcache_get_status(true)['scripts']`

These endpoints are used by the audit script to read worker-process metrics (CLI `opcache_get_status()` would return CLI stats, not worker stats).

## Rector-Enforced OPcache Patterns

The following Rector rules in `rector.php` enforce OPcache-friendly code (all in `warn` mode with `TODO: [opcache]` messages):

| Pattern | Why it hurts OPcache |
|---|---|
| Dynamic `include`/`require` | Prevents compile-time file resolution |
| Missing type declarations | Prevents type-based JIT optimization |
| Variable variables (`$$var`) | Prevents compile-time variable resolution |
| Error suppression (`@`) | Adds per-call overhead |
| `global` keyword | Prevents scope-level optimization |

## Commands

- `./run audit:opcache` — run the full OPcache audit
- `./run generate:preload` — regenerate `preload.php`
- `./run prod:check` — run all production readiness checks (includes OPcache audit)

## Rules

- **Never manually edit `preload.php`** — it is auto-generated. Run `./run generate:preload` instead.
- All OPcache commands must run inside Docker.
- When adding new application classes, `preload.php` should be regenerated to include them.
- The preload generator's topological sort handles class dependency ordering — do not manually reorder entries.
- Dev and production use the same base `opcache.ini`; dev overrides via `zzz-` prefix file loading order.
- The audit script distinguishes dev from production via `ini_get('opcache.validate_timestamps')`.