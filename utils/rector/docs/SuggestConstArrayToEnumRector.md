# SuggestConstArrayToEnumRector

Flags `readonly` classes that have two or more `public const array` constants whose values are lists of string literals, suggesting migration to a backed enum with `#[Group]` attributes.

**Category:** Code Quality / Enum Design
**Mode:** `warn` only
**Auto-fix:** No

## Rationale

Enumerations define sets. A `readonly` class with multiple `public const array` properties each holding a list of strings is encoding a grouping concept with a weaker tool. A backed enum with `#[Group]` attributes makes the set membership explicit, allows case-level metadata, enables exhaustive matching, and prevents adding arbitrary new values at call sites. The class form cannot enforce that the lists remain disjoint or complete.

## What It Detects

`Class_` nodes that are `readonly` and contain at least two `public const array` constants whose values are non-empty lists of string literals (no keys, no non-string elements).

```php
readonly class DevFilter
{
    public const array dev_vendors = ['/vendor/phpunit/', '/vendor/phpstan/'];
    public const array excluded_dirs = ['/var/cache/', '/tests/'];
}
```

## Transformation

### In `warn` mode

Adds `// TODO: Consider migrating const arrays to a backed enum with #[Group] attributes` above the class declaration.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` disables the rule entirely |
| `message` | `string` | `'TODO: Consider migrating const arrays to a backed enum with #[Group] attributes'` | Comment text |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(SuggestConstArrayToEnumRector::class, [
    'mode' => 'warn',
    'message' => 'TODO: Consider migrating const arrays to a backed enum with #[Group] attributes',
]);
```

## Example

### Before
```php
readonly class DevFilter
{
    public const array dev_vendors = [
        '/vendor/phpunit/',
        '/vendor/phpstan/',
    ];

    public const array excluded_dirs = [
        '/var/cache/',
        '/tests/',
    ];
}
```

### After
```php
// TODO: Consider migrating const arrays to a backed enum with #[Group] attributes
readonly class DevFilter
{
    public const array dev_vendors = [
        '/vendor/phpunit/',
        '/vendor/phpstan/',
    ];

    public const array excluded_dirs = [
        '/var/cache/',
        '/tests/',
    ];
}
```

## Resolution

When you see the TODO comment:
1. Create a backed string enum where each case value is one of the strings from the arrays.
2. Add a `#[Group]` attribute (or equivalent grouping attribute) to each case to record which former const array it belonged to.
3. Replace consumers that iterate the const arrays with enum case filtering by group.
4. Delete the original `readonly` class.

## Related Rules

- [`SuggestDuplicateStringConstantRector`](SuggestDuplicateStringConstantRector.md) — flags individual duplicate strings; const arrays are the next level of the same problem
- [`SuggestEnumForStringPropertyRector`](SuggestEnumForStringPropertyRector.md) — flags string properties on DataModel classes whose values form a known set
