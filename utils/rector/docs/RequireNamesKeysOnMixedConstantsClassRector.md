# RequireNamesKeysOnMixedConstantsClassRector

Requires a `#[NamesKeys]` (or `#[KeyRegistry]`) attribute on classes that have a minimum number of public string constants alongside other members such as methods.

**Category:** Constants Class Design
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

A class that mixes string constants with methods often serves as both a key registry and a service. The project convention (ADR-007) still requires the key registry role to be declared with `#[NamesKeys]` / `#[KeyRegistry]`, even when the class is not a pure constants holder. Without the annotation there is no signal that distinguishes these string constants (which name something in the domain) from incidental magic strings.

This rule is the companion to `RequireNamesKeysOnConstantsClassRector`, which only handles pure constants classes. This rule applies when the class also has methods or properties.

## What It Detects

Classes (not necessarily `readonly`) that:
- Have at least `minConstants` public `string`-typed constants.
- Do not already have the configured `attributeClass`.
- Do not have any `excludedAttributes` (e.g., `#[ViewModel]`).
- Do not use any `excludedTraits` (e.g., `DataModel` — whose constants are property keys, not naming keys).

## Transformation

### In `auto` mode

The rule prepends `#[NamesKeys(source: 'TODO: describe source')]` to the class.

### In `warn` mode

A TODO comment is prepended to the class declaration:

```
// TODO: [RequireNamesKeysOnMixedConstantsClassRector] Metrics has 3 string constants — add #[NamesKeys] to declare what they name (ADR-007).
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `attributeClass` | `string` | `''` | FQN of the attribute to require (e.g., `KeyRegistry::class`) |
| `minConstants` | `int` | `3` | Minimum number of public `string` constants to trigger the rule |
| `excludedTraits` | `string[]` | `[]` | Classes using these traits are skipped |
| `excludedAttributes` | `string[]` | `[]` | Classes already annotated with these are skipped |
| `mode` | `string` | `'warn'` | `'auto'` to add the attribute, `'warn'` to add a TODO comment |
| `message` | `string` | see source | `sprintf` template; receives `(className, constCount)` |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RequireNamesKeysOnMixedConstantsClassRector::class, [
    'attributeClass' => KeyRegistry::class,
    'minConstants' => 3,
    'excludedTraits' => [DataModel::class],
    'excludedAttributes' => [ViewModel::class],
    'mode' => 'warn',
    'message' => 'TODO: [RequireNamesKeysOnMixedConstantsClassRector] %s has %d string constants — add #[NamesKeys] to declare what they name (ADR-007).',
]);
```

## Example

### Before

```php
readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(): void {}
}
```

### After

```php
// TODO: [RequireNamesKeysOnMixedConstantsClassRector] Metrics has 3 string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(): void {}
}
```

## Resolution

When you see the TODO comment from this rule:
1. Determine what these string constants name (e.g., "metric field names for the telemetry system").
2. Add `#[KeyRegistry(source: 'description')]` to the class.
3. If the class also has methods and all constants are only referenced internally via `self::`, consider `SuggestEnumForInternalOnlyConstantsRector`'s guidance about migrating to a backed enum.

## Related Rules

- [`RequireNamesKeysOnConstantsClassRector`](RequireNamesKeysOnConstantsClassRector.md) — same requirement for pure constants classes (no methods)
- [`SuggestEnumForKeyRegistryWithMethodsRector`](SuggestEnumForKeyRegistryWithMethodsRector.md) — suggests moving constants to a backed enum when `#[KeyRegistry]` is already present alongside methods
- [`SuggestEnumForInternalOnlyConstantsRector`](SuggestEnumForInternalOnlyConstantsRector.md) — suggests backed enum migration when constants are only used internally
