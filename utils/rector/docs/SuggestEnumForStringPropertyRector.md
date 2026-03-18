# SuggestEnumForStringPropertyRector

Adds a TODO comment on string properties of DataModel classes when their known values can be inferred from `#[Describe]` defaults or in-class comparisons, and adds a call-site comment on `from()` array items that supply one of those values.

**Category:** Code Quality / Enum Design
**Mode:** `warn` only
**Auto-fix:** No

## Rationale

Enumerations define sets. A `string` property on a DataModel class that only ever holds a handful of known values is a set masquerading as a primitive. Encoding the set as a backed enum restricts the values that can be assigned, makes the choices visible to static analysis, and enables exhaustive matching. This rule surfaces the evidence — known default values and comparison strings found in the class body — so that the migration can begin.

## What It Detects

The rule operates on two node types:

**`Class_` nodes** that use a configured DataModel trait and have at least one non-nullable `string` property. For each such property it collects known values from:
- `#[Describe(['default' => 'value'])]` attribute arguments
- Strict comparisons (`===`, `!==`) against the property within the class

If any values are found, a TODO comment is added above the property.

**`StaticCall` nodes** for `ClassName::from([...])` on DataModel classes. If an array item supplies a string literal (or `?? 'fallback'`) to a string property that already has a TODO, a call-site TODO is added above that item.

## Transformation

### In `warn` mode

On the property:
```
// TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. $<prop> has values: '<v1>', '<v2>'. Extract to a backed enum.
```

On the call site item:
```
// TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. '<value>' is a value of <Class>::$<prop>. Replace with enum case.
```

The exact messages are configured in `rector.php`.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `dataModelTraits` | `string[]` | `[]` | Fully qualified trait names that identify a DataModel class |
| `describeAttrs` | `string[]` | `[]` | Fully qualified attribute class names equivalent to `#[Describe]` |
| `mode` | `string` | `'warn'` | Only `'warn'` is effective; `'auto'` disables the rule entirely |
| `message` | `string` | `'TODO: $%s has known values: %s — consider extracting to an enum'` | Property-level comment template; `%s` = prop name, `%s` = quoted values |
| `callSiteMessage` | `string` | `'TODO: %s is a value of %s::$%s — consider replacing with an enum case'` | Call-site comment template; `%s` = quoted value, `%s` = class name, `%s` = prop name |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(SuggestEnumForStringPropertyRector::class, [
    'dataModelTraits' => [DataModel::class, \ZeroToProd\Thryds\Helpers\DataModel::class],
    'describeAttrs' => [Describe::class, \ZeroToProd\Thryds\Helpers\Describe::class],
    'mode' => 'warn',
    'message' => 'TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. $%s has values: %s. Extract to a backed enum.',
    'callSiteMessage' => 'TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. %s is a value of %s::$%s. Replace with enum case.',
]);
```

## Example

### Before (Describe default)
```php
class AppConfig
{
    use DataModel;

    #[Describe(['default' => 'production'])]
    public string $env;
}
```

### After
```php
class AppConfig
{
    use DataModel;

    // TODO: $env has known values: 'production' — consider extracting to an enum
    #[Describe(['default' => 'production'])]
    public string $env;
}
```

### Before (comparison values + call site)
```php
class ServerConfig
{
    use DataModel;

    #[Describe(['default' => 'production'])]
    public string $mode;

    public static function isDev(mixed $value, array $context): bool
    {
        return ($context['mode'] ?? 'production') === 'development';
    }
}

$ServerConfig = ServerConfig::from([
    ServerConfig::mode => $_ENV['APP_ENV'] ?? 'production',
]);
```

### After
```php
class ServerConfig
{
    use DataModel;

    // TODO: $mode has known values: 'development', 'production' — consider extracting to an enum
    #[Describe(['default' => 'production'])]
    public string $mode;

    public static function isDev(mixed $value, array $context): bool
    {
        return ($context['mode'] ?? 'production') === 'development';
    }
}

$ServerConfig = ServerConfig::from([
    // TODO: 'production' is a value of ServerConfig::$mode — consider replacing with an enum case
    ServerConfig::mode => $_ENV['APP_ENV'] ?? 'production',
]);
```

## Resolution

When you see the TODO comment on a property:
1. Create a backed string enum containing the listed values as cases (e.g. `enum AppEnv: string { case Production = 'production'; }`).
2. Change the property type from `string` to the new enum (e.g. `public AppEnv $env;`).
3. Update all `from()` call sites to pass the enum case value (e.g. `AppEnv::Production->value`).
4. Update all comparison sites to compare against enum cases rather than strings.

When you see the TODO comment on a call-site item:
1. Replace the string literal with the appropriate enum case value (e.g. `AppEnv::Production->value`).

## Related Rules

- [`SuggestConstArrayToEnumRector`](SuggestConstArrayToEnumRector.md) — flags const arrays of strings that should become an enum
- [`SuggestDuplicateStringConstantRector`](SuggestDuplicateStringConstantRector.md) — flags the same values being repeated as plain string literals
