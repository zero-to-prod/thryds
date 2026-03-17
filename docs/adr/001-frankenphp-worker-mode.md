# ADR-001: FrankenPHP Worker Mode

## Status
Accepted

## Context
Traditional PHP deployments (FPM, mod_php) boot the application on every request — autoloading, config parsing, route registration, and template engine setup all happen per-request. This creates a performance floor that caching alone cannot eliminate.

Alternatives considered:
- **PHP-FPM** — mature, but per-request boot is inherent to the model.
- **Swoole/OpenSwoole** — persistent worker model, but requires a separate web server (Nginx) and a custom event loop that diverges from standard PHP patterns.
- **RoadRunner** — Go-based worker, but adds a non-PHP binary to the stack.

## Decision
Use FrankenPHP in worker mode as the application server. The app boots once (`App::boot()`), then handles requests in a loop via `frankenphp_handle_request()`.

## Consequences
- **Boot cost is paid once.** Config, Blade, Router, and Vite are constructed at startup and reused across all requests.
- **Static state persists across requests.** Any mutable static variable (e.g., `RequestId::$current`) must be reset in a `finally` block after each request. This is a class of bug that doesn't exist in FPM.
- **OPcache preloading is effective.** Because the worker process is long-lived, preloaded bytecode stays in shared memory and is used for every request.
- **Caddy is built in.** No separate Nginx/Apache config — HTTPS, HTTP/2, and HTTP/3 work out of the box.
- **ext-frankenphp stubs are incomplete.** PHPStan level is capped at 2 because higher levels produce false positives from missing stub definitions (see ADR-003).