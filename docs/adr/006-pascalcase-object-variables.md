# ADR-006: PascalCase Object Variables

## Status
Accepted

## Context
PHP convention is `$camelCase` for all variables. This makes it impossible to distinguish `$config` (a Config instance) from `$config` (a string) at a glance. When reading code — especially as an AI agent scanning for type information — the variable name alone carries no type signal.

## Decision
Object instance variables use PascalCase matching their type name (`$Config`, `$Blade`, `$Router`). Primitive variables use snake_case (`$base_dir`, `$cache_dir`). Rector rules enforce both conventions automatically.

## Consequences
- **Type is visible in the name.** `$Config->isProduction()` tells you the type without hovering or reading the declaration. `$base_dir` tells you it's a string.
- **Grep-friendly.** Searching for `$Config` finds all usages of the Config instance across the codebase. No ambiguity with other variables.
- **Breaks PHP convention.** This diverges from PSR and community norms. New contributors will be surprised. Rector auto-fixes non-conforming code, so the learning curve is absorbed by tooling.
- **Rector enforces it.** `RenamePropertyToMatchTypeNameRector` and `RenamePrimitiveVarToSnakeCaseRector` handle renaming automatically — developers don't need to remember the rule.
