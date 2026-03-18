# ADR-009: Hot Reloading in Development

## Status
Accepted

## Context

Worker mode (ADR-001) boots the application once and keeps it resident in memory across requests. This makes hot reloading non-trivial: a changed PHP file is invisible to a running worker because the old bytecode is already compiled and cached in OPcache. In traditional FPM deployments this problem doesn't exist — each request forks a fresh process that re-evaluates every file.

Two layers of state must be invalidated when source files change:

<a id="context-opcache"></a>
1. **OPcache bytecode** — compiled PHP files stored in shared memory. Production disables timestamp validation (`opcache.validate_timestamps=0`) for maximum throughput. Without overriding this in development, editing a source file has no effect until the worker restarts.

<a id="context-worker"></a>
2. **The resident worker process itself** — even after OPcache is cleared, the worker's in-memory object graph (App, Router, Blade engine) was built from the old code. A cache flush alone is insufficient; the worker must restart and re-run `App::boot()`.

## Decision

<a id="decision-compose"></a>
Hot reloading is enabled exclusively through `compose.development.yaml`, which is never applied in production. It injects two Caddy directives via the `CADDY_PHP_SERVER_EXTRA_DIRECTIVES` environment variable:

```yaml
environment:
  CADDY_PHP_SERVER_EXTRA_DIRECTIVES: |
    hot_reload
    worker {
      file /app/public/index.php
      watch /app/src/**/*.php
      watch /app/public/**/*.php
      watch /app/templates/**/*.php
    }
```

The `hot_reload` directive activates FrankenPHP's built-in file-system watcher. The `worker` block's `watch` entries scope watching to the three directories that contain application code. When a `.php` file in any of those trees changes, FrankenPHP kills the current worker and starts a fresh one, re-running `App::boot()` from scratch.

<a id="decision-opcache"></a>
OPcache is configured separately in `docker/php/opcache-dev.ini`, overriding the production settings:

```ini
opcache.validate_timestamps=1
opcache.revalidate_freq=0
opcache.preload=
```

`validate_timestamps=1` re-enables file stat checks so OPcache does not serve stale bytecode. `revalidate_freq=0` instructs it to check on every request rather than on a timer. Preloading is disabled because it would lock compiled bytecode into shared memory at worker startup, defeating both settings above.

<a id="decision-dockerfile"></a>
The production `opcache.ini` keeps `validate_timestamps=0` and `opcache.preload=/app/preload.php` unchanged — development overrides are contained entirely within `opcache-dev.ini`, which the development Dockerfile stage installs alongside `php.ini-development`:

```dockerfile
# development stage
COPY docker/php/php.ini-development "$PHP_INI_DIR/php.ini"
COPY docker/php/opcache-dev.ini "$PHP_INI_DIR/conf.d/opcache.ini"
```

Frontend hot reloading is handled independently by Vite. A separate `dev` container runs `npm run dev` (Vite's dev server on port 5173) and provides HMR for JavaScript and CSS changes. This is orthogonal to the PHP worker restart path and requires no coordination with FrankenPHP.

## Consequences

- **PHP changes are visible immediately.** Saving any file under `src/`, `public/`, or `templates/` triggers a worker restart. The next request boots against the updated code.
- **Worker restarts are synchronous with changes, not with requests.** There is a brief window between file write and worker readiness. The first request after a restart pays the full `App::boot()` cost; subsequent requests hit the warm cache.

<a id="consequences-static-state"></a>
- **Static state hygiene is enforced by design.** Because the worker restarts on every change, any state leak that survives across requests is masked during development — only appearing in production where workers are long-lived. `RequestId::reset()` in the `finally` block of `public/index.php` addresses the known case; new static state must follow the same pattern.

<a id="consequences-preload"></a>
- **Preloading is incompatible with development hot reload.** Disabling it in `opcache-dev.ini` means the first request after a worker restart is slower than in production. This is an acceptable tradeoff for correctness.

- **Production is unaffected.** The `hot_reload` directive, file watchers, and OPcache overrides exist only in the development compose overlay and the development Dockerfile stage. Running `APP_ENV=production docker compose -f compose.yaml up -d` loads neither.
