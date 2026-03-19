# RemoveDefaultsAndApplyAtCallsiteRector

Removes default values from function, method, and attribute constructor parameters and adds those defaults explicitly at every call site that was relying on them.

**Category:** Code Quality (one-shot migration tool)
**Mode:** `auto` only (warn mode is a no-op)
**Auto-fix:** Yes

## Rationale

Default parameter values create implicit behaviour: callers that omit an argument silently receive the default. When a default is "the only correct value" for most callers, it is better to make it explicit at every call site and remove the default from the signature. This forces future callers to consciously choose a value rather than relying on a hidden assumption.

Common scenarios:
- A `bool $debug = false` param that should always be set explicitly.
- A PHP `#[Attribute]` constructor with a `$superglobals = []` default that should appear verbatim in every usage.
- A method default that represents a domain decision, not a fallback.

## What It Does

For **PHP attribute constructors** (auto-discovered): uses `ReflectionClass` to find optional constructor parameters of the attribute class, then adds each missing arg as a named argument at the callsite. Because reflection works from the compiled class on disk, this operates correctly across files in Rector's parallel worker mode — no static state is shared between workers.

For **functions and methods** (opt-in, same-file only): uses AST analysis within the same file. The definition and callsite must be in the same file for this path; cross-file function/method migration is not supported.

For **attribute class definitions** (in the same file as the class): removes defaults from `__construct` parameters once they have been inlined at callsites.

## Transformation

### In `auto` mode

1. **Callsite (attribute):** Any `#[MyAttr(...)]` that omits an argument with a default gets that default added as a named arg, e.g. `superglobals: []`.
2. **Definition (attribute class constructor):** Once defaults are inlined, the default is removed from the parameter declaration (making it required).
3. Call sites that already pass the argument (positionally or by name) are left untouched.

### In `warn` mode

No-op. This is a structural refactoring — there is no useful warning-only form.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to transform. `'warn'` is a no-op. |
| `targetFunctions` | `string[]` | `[]` | Fully-qualified function names (same-file only), e.g. `['MyNs\\greet']`. Empty = skip all functions. |
| `targetMethods` | `string[]` | `[]` | `'ClassName::methodName'` strings (same-file only). Empty = skip all methods. |
| `targetAttributes` | `string[]` | `[]` | Attribute class FQCNs to restrict to, e.g. `['App\\Attributes\\MyAttr']`. Empty = process ALL non-internal `#[Attribute]` classes. |

`targetFunctions` and `targetMethods` are opt-in only: empty lists (the default) mean skip entirely. `targetAttributes` empty means process all.

## Registration in rector.php

The rule is registered with empty target lists for attributes (process all `#[Attribute]` classes) and without function/method targets:

```php
$rectorConfig->ruleWithConfiguration(RemoveDefaultsAndApplyAtCallsiteRector::class, [
    'mode' => 'auto',
]);
```

To restrict to specific attribute classes:

```php
$rectorConfig->ruleWithConfiguration(RemoveDefaultsAndApplyAtCallsiteRector::class, [
    'mode' => 'auto',
    'targetAttributes' => ['MyApp\\Attributes\\KeyRegistry'],
]);
```

To also migrate functions/methods in the same file:

```php
$rectorConfig->ruleWithConfiguration(RemoveDefaultsAndApplyAtCallsiteRector::class, [
    'mode' => 'auto',
    'targetFunctions' => ['MyApp\\Utils\\greet'],
    'targetMethods'   => ['MyApp\\Mailer::send'],
]);
```

## Example

### Attribute constructor — before

```php
#[Attribute]
class KeyRegistry
{
    public function __construct(
        public readonly string $source,
        public array $superglobals = [],
        public string $addKey = '',
    ) {}
}

#[KeyRegistry('vite_entry_points')]                        // relying on defaults
#[KeyRegistry('vite_entry_points', addKey: 'custom')]     // already explicit for addKey
```

### Attribute constructor — after

```php
#[Attribute]
class KeyRegistry
{
    public function __construct(
        public readonly string $source,
        public array $superglobals,                        // default removed
        public string $addKey,                             // default removed
    ) {}
}

#[KeyRegistry('vite_entry_points', superglobals: [], addKey: '')]     // defaults applied
#[KeyRegistry('vite_entry_points', superglobals: [], addKey: 'custom')]  // addKey kept, superglobals added
```

### Function (same-file) — before

```php
function greet(string $name, string $greeting = 'Hello'): string
{
    return "{$greeting}, {$name}!";
}

greet('Alice');           // relying on default
greet('Bob', 'Hi');       // already explicit
```

### Function (same-file) — after

```php
function greet(string $name, string $greeting): string
{
    return "{$greeting}, {$name}!";
}

greet('Alice', greeting: 'Hello');  // default applied explicitly
greet('Bob', 'Hi');                 // unchanged
```

## Cross-file behaviour

For **attributes**: reflection is used, so the rule works across files and in parallel worker mode. When Rector processes a callsite file, it reflects on the attribute class from the autoloader — no shared state between worker processes.

For **functions and methods**: AST analysis only. The rule collects defaults from definitions in the current file and applies them to callsites in the same file. Cross-file function/method migration requires two passes: one to update callsites, one to remove defaults.

## Execution order caveat

When running `fix:rector` over the whole codebase in a single pass, the attribute class definition file and its callsite files may be processed in any order. If the definition file is processed first (defaults removed), the reflected defaults are gone when callsite files are subsequently processed. Run `fix:rector` in dry-run mode first to preview, then apply changes file-by-file or restrict to specific files if needed:

```bash
# Step 1: process only definition + specific callsite files together
vendor/bin/rector process src/Attributes/KeyRegistry.php src/Header.php

# Step 2: run full fix to catch remaining callsites
vendor/bin/rector process
```

## Caveats

- Does not handle variadic parameters.
- Skips PHP built-in classes (e.g. `\Attribute`, `\Iterator`) — their defaults are never inlined.
- Skips attribute classes that cannot be autoloaded (e.g. classes defined only in test fixture files).
- For method calls on instances (`$obj->method()`), the class is inferred from the local registry: if exactly one registered method has that name it is matched; otherwise provide the FQN via `targetMethods`.
- Always uses named argument syntax for the added arg (`param: value`) to keep changes self-documenting.

## Related Rules

- `AddNamedArgWhenVarMismatchesParamRector` — adds named args when the variable name differs from the param name.
- `RequireNamedArgForBoolParamRector` — requires named args for boolean parameters.
