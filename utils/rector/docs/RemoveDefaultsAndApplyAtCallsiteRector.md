# RemoveDefaultsAndApplyAtCallsiteRector

Removes default values from function, method, and attribute constructor parameters and adds those defaults explicitly at every call site that was relying on them.

**Category:** Code Quality (one-shot migration tool)
**Mode:** `auto` only (warn mode is a no-op)
**Auto-fix:** Yes

## Rationale

Default parameter values create implicit behaviour: callers that omit an argument silently receive the default. When a default is "the only correct value" for most callers, it is better to make it explicit at every call site and remove the default from the signature. This forces future callers to consciously choose a value rather than relying on a hidden assumption.

Common scenarios:
- A `bool $debug = false` param that should always be set explicitly.
- A PHP `#[Attribute]` constructor with a `$addKey = '...'` default that should appear verbatim in every usage.
- A method default that represents a domain decision, not a fallback.

## What It Detects

Functions, methods, and PHP attribute constructors whose parameters have default values, where the default was registered in `targetFunctions`, `targetMethods`, or `targetAttributes`.

At each call site that omits a registered defaulted argument, the rule adds the default value explicitly using **named argument syntax** so the added argument is self-documenting.

## Transformation

### In `auto` mode

1. For every defaulted parameter in a registered callable, the default is removed from the parameter declaration (making the parameter required).
2. Every call site that was omitting that argument receives the default value added as a named argument: `greeting: 'Hello'`.
3. Call sites that already pass the argument (positionally or by name) are left untouched.

### In `warn` mode

No-op. This is a structural refactoring — there is no useful warning-only form.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to transform. `'warn'` is a no-op. |
| `targetFunctions` | `string[]` | `[]` | Fully-qualified function names to migrate, e.g. `['MyNs\\greet']`. Empty list with the key present = migrate ALL functions (use with care). |
| `targetMethods` | `string[]` | `[]` | `'ClassName::methodName'` strings, e.g. `['App\\Mailer::send']`. Empty list with the key present = migrate ALL methods. |
| `targetAttributes` | `string[]` | `[]` | Attribute class FQCNs, e.g. `['App\\Attributes\\MyAttr']`. Empty list with the key present = migrate ALL attributes. |

**Important:** If none of `targetFunctions`, `targetMethods`, `targetAttributes` appear as keys in the configuration, the rule is a **no-op**. This is the safe default for a permanent `rector.php` registration. The rule activates only when at least one of the three target keys is present.

## Registration in rector.php

The rule is registered as a no-op (no target keys) so it can live permanently in `rector.php` without modifying any code:

```php
$rectorConfig->ruleWithConfiguration(RemoveDefaultsAndApplyAtCallsiteRector::class, [
    'mode' => 'auto',
]);
```

To run a migration, add a target key:

```php
$rectorConfig->ruleWithConfiguration(RemoveDefaultsAndApplyAtCallsiteRector::class, [
    'mode' => 'auto',
    'targetFunctions' => ['MyApp\\Utils\\greet'],
    'targetMethods'   => ['MyApp\\Mailer::send'],
    'targetAttributes' => ['MyApp\\Attributes\\KeyRegistry'],
]);
```

## Example

### Function — before

```php
function greet(string $name, string $greeting = 'Hello'): string
{
    return "{$greeting}, {$name}!";
}

greet('Alice');           // relying on default
greet('Bob', 'Hi');       // already explicit
```

### Function — after

```php
function greet(string $name, string $greeting): string
{
    return "{$greeting}, {$name}!";
}

greet('Alice', greeting: 'Hello');  // default applied explicitly
greet('Bob', 'Hi');                 // unchanged
```

### Attribute constructor — before

```php
#[Attribute]
class KeyRegistry
{
    public function __construct(
        public readonly string $source,
        public readonly string $addKey = '1. Add constant. 2. Register directive.',
    ) {}
}

#[KeyRegistry('vite_entry_points')]                        // relying on default
#[KeyRegistry('vite_entry_points', addKey: 'custom')]     // already explicit
```

### Attribute constructor — after

```php
#[Attribute]
class KeyRegistry
{
    public function __construct(
        public readonly string $source,
        public readonly string $addKey,                    // default removed
    ) {}
}

#[KeyRegistry('vite_entry_points', addKey: '1. Add constant. 2. Register directive.')]  // default applied
#[KeyRegistry('vite_entry_points', addKey: 'custom')]                                   // unchanged
```

## Cross-file usage

The rule uses `FileNode`-based traversal with static state. Within a single file, both the definition and call sites are transformed atomically. Across files, Rector's multi-pass architecture (it re-runs until no changes) ensures convergence: definitions are collected on the first pass, call sites in the same or later passes are updated, and defaults are removed once all call sites have been updated.

For single-file test fixtures the rule works in one pass because definition and call sites share the same `FileNode`.

## Caveats

- Only removes defaults that are listed in the target configuration. Unlisted callables are never touched.
- Does not handle variadic parameters.
- For method calls on instances (`$obj->method()`), the class is inferred from the registry: if exactly one registered method has that name, it is matched. If multiple classes have a method of the same name, provide the class name via `targetMethods` with the FQN.
- Always uses named argument syntax for the added arg (`param: value`), even when appending positionally would be valid, to keep the change self-documenting.

## Related Rules

- `AddNamedArgWhenVarMismatchesParamRector` — adds named args when the variable name differs from the param name.
- `RequireNamedArgForBoolParamRector` — requires named args for boolean parameters.
