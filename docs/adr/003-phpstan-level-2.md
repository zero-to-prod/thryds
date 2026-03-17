# ADR-003: PHPStan at Level 2

## Status
Accepted

## Context
PHPStan's analysis levels range from 1 (basic) to 9 (strictest). Higher levels catch more type errors but require accurate stubs for all extensions. The `ext-frankenphp` extension provides `frankenphp_handle_request()`, `frankenphp_log()`, and `FRANKENPHP_LOG_LEVEL_*` constants at runtime, but its stubs are incomplete — levels 3+ produce false positives on these symbols.

## Decision
Set PHPStan to level 2. Delegate type enforcement to custom Rector rules (`RequireReturnTypeRector`, `RequireParamTypeRector`, `RequireTypedPropertyRector`) which operate on the AST and are unaffected by missing stubs.

## Consequences
- **No false-positive noise.** Developers and agents trust PHPStan output — every reported issue is real.
- **Type coverage is still enforced.** Rector rules require type declarations on all parameters, return types, and properties. The gap between level 2 and higher levels is partially closed.
- **Revisit when stubs improve.** If ext-frankenphp publishes complete stubs, the level can be raised. A PHPStan baseline file would allow raising the level while ignoring known stub issues.
