# ADR-003: PHPStan at Level 9

## Status
Accepted (updated 2026-03-19: raised from level 2 to level 9)

## Context
PHPStan's analysis levels range from 1 (basic) to 9 (strictest). The original decision pinned at level 2 due to anticipated false positives from incomplete `ext-frankenphp` stubs. In practice, `ext-frankenphp` produces no false positives at any level — all errors surfaced at higher levels were real type annotation gaps and a real operator-precedence bug in `AppEnv::fromEnv()`.

## Decision
Set PHPStan to level 9 (maximum). All 31 errors found at level 9 were fixed directly rather than baselined. Rector rules (`RequireReturnTypeRector`, `RequireParamTypeRector`, `RequireTypedPropertyRector`) complement PHPStan by enforcing declarations at the AST level.

## Consequences
- **Maximum static analysis coverage.** Level 9 catches missing iterable value types, non-nullable cast misuse, and return type mismatches.
- **No false-positive noise.** Every reported issue is real — ext-frankenphp stubs are complete enough at this level.
- **Type coverage is fully enforced.** PHPStan + Rector provide overlapping, complementary coverage.
