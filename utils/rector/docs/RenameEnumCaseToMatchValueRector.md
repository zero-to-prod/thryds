# RenameEnumCaseToMatchValueRector

Rename backed enum cases so that the case name exactly matches the string backing value.

**Category:** Naming
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The project principle "Enumerations define sets" implies that an enum case is the canonical identifier for a concept, not merely a label for a string. When `case Production = 'production'` exists, two names exist for one concept. Aligning the case name to the value — `case production = 'production'` — makes the enum a zero-ambiguity lookup table: the identifier in code and the serialized form are identical. This is especially important for enums used as route patterns, config keys, or database values where the string and the PHP identifier must stay in sync.

The rule also rewrites all `ClassName::CaseName` fetch sites to match the renamed case. Cases whose string value is not a valid PHP identifier are skipped.

## What It Detects

A backed string enum case where `case Name = 'value'` and `Name !== 'value'`, e.g. `case Production = 'production'` or `case InProgress = 'in_progress'`. Also detects `ClassName::CaseName` fetch sites that reference the old case name.

## Transformation

### In `auto` mode
Renames the `EnumCase` identifier to match the string value, and updates every `ClassName::CaseName` reference in the same file.

### In `warn` mode
Adds the configured `message` as a `//` comment above the case declaration or fetch site (idempotent).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to rewrite, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`): `mode => 'auto'`.

## Example

### Before
```php
enum AppEnv: string
{
    case Production = 'production';
    case Development = 'development';
}
```

### After
```php
enum AppEnv: string
{
    case production = 'production';
    case development = 'development';
}
```

### Before (with usage sites)
```php
enum Env: string
{
    case Production = 'production';
    case Development = 'development';
}

function example(): void
{
    $env = Env::Production;
    $val = Env::Development->value;
}
```

### After
```php
enum Env: string
{
    case production = 'production';
    case development = 'development';
}

function example(): void
{
    $env = Env::production;
    $val = Env::development->value;
}
```

## Related Rules

- [`RenamePropertyToMatchTypeNameRector`](RenamePropertyToMatchTypeNameRector.md) — applies analogous name-equals-identity logic to class properties typed with enum or class types
