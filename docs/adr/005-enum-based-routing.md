# ADR-005: Enum-Based Routing

## Status
Accepted

## Context
Most PHP routers use string patterns for route definitions (`$router->get('/users/{id}', ...)`). String-based routes are duplicated across registration, URL generation, and templates — a rename requires finding and updating every occurrence. There's no compile-time check that a route exists or that all routes are registered.

## Decision
Define routes as a backed string enum (`enum Route: string`). Route registration, URL generation, and template links all reference `Route::case_name->value`. Five Rector rules enforce this:
- `ForbidStringRoutePatternRector` — no magic string route patterns
- `RequireRouteEnumInMapCallRector` — `Router->map()` must use `Route::case->value`
- `ForbidHardcodedRouteStringRector` — no hardcoded route strings anywhere
- `RequireAllRouteCasesRegisteredRector` — every enum case must be registered
- `RequireRouteTestRector` — every enum case must have a test

## Consequences
- **Single source of truth.** `Route.php` is the complete list of routes. Adding a route means adding an enum case.
- **Refactoring is safe.** Renaming a route case propagates through the IDE and Rector catches any remaining string references.
- **Compile-time completeness.** Rector verifies that every enum case is registered and tested — an unregistered route is caught before commit, not at runtime.
- **Blade templates use enum values.** `href="{{ Route::about->value }}"` instead of `href="/about"`. A separate linter (`check-blade-routes.php`) catches hardcoded paths in templates.
