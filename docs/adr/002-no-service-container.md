# ADR-002: No Service Container

## Status
Accepted

## Context
Most PHP frameworks use a dependency injection container (Laravel's Container, Symfony DI, PHP-DI) to resolve class dependencies at runtime. Containers introduce indirection: dependencies are registered as string keys or class names, resolved via reflection or factory closures, and wired together at request time.

## Decision
Wire all dependencies explicitly in `App::boot()`. Controllers receive their dependencies through constructor arguments at registration time, not through container resolution at dispatch time.

## Consequences
- **Every dependency is traceable from source.** An agent or developer can follow `App::boot()` → `WebRoutes::register()` → `new HomeController($Blade)` without consulting a service provider or config file.
- **No reflection at runtime.** No `ReflectionClass`, no `class_exists()`, no dynamic instantiation — this supports OPcache optimization and static analysis.
- **No lazy loading.** All objects are constructed at boot, even if a request doesn't use them. Acceptable because the object graph is small and boot happens once per worker lifetime (see ADR-001).
- **Blade's internal Container is the sole exception.** `BladeContainer` is required by the Blade template engine for directive resolution — this is isolated to `App::bootBlade()` and doesn't leak into application code.
