# class_attributes_separation

## Tool

PHP CS Fixer (built-in rule)

## What it does

Automatically enforces consistent blank-line spacing between class elements: constants,
properties, methods, trait imports, and enum cases. Inserts or removes blank lines on
`php-cs-fixer fix`.

## Why it matters

When an agent scans a class, it uses visual boundaries to identify where one element
ends and the next begins. Consistent blank lines create clear segments — an agent can
reliably detect "this is a new method" by the blank line before it, rather than parsing
every token.

## Configuration

```php
// .php-cs-fixer.php
return (new Config())
    ->setRules([
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'none',
                'property' => 'none',
                'method' => 'one',
                'trait_import' => 'none',
                'case' => 'none',
            ],
        ],
    ]);
```

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `elements` | `array<string, string>` | `['const' => 'one', 'method' => 'one', 'property' => 'one', 'trait_import' => 'none', 'case' => 'none']` | Spacing rule for each element type |

### Element types

| Key | Description |
|---|---|
| `const` | Class constants |
| `property` | Properties (including promoted) |
| `method` | Methods (including abstract) |
| `trait_import` | `use` statements for traits |
| `case` | Enum cases |

### Spacing values

| Value | Effect |
|---|---|
| `none` | No blank line between consecutive elements of this type |
| `one` | Exactly one blank line between consecutive elements of this type |
| `only_if_meta` | Blank line only if the element has a docblock or attribute |

## Before / After

### Before

```php
class Config
{
    public const APP_ENV = 'APP_ENV';
    public const DB_HOST = 'DB_HOST';
    public string $app_env;

    public string $db_host;
    public function __construct() {}
    public function isProduction(): bool { return true; }
}
```

### After (with `const => 'none'`, `property => 'none'`, `method => 'one'`)

```php
class Config
{
    public const APP_ENV = 'APP_ENV';
    public const DB_HOST = 'DB_HOST';
    public string $app_env;
    public string $db_host;

    public function __construct() {}

    public function isProduction(): bool { return true; }
}
```

## Implementation notes

- Built-in CS Fixer rule — no custom code required.
- Add to the `setRules()` array in `.php-cs-fixer.php`.
- The recommended config groups constants and properties tightly (no blank lines within
  each group) while separating methods with one blank line. This reflects the common
  reading pattern: scan the data at the top, then read methods individually.
- `only_if_meta` is useful for properties: a property with a docblock gets visual
  separation, a bare property does not.
- This rule is safe (not risky) — it only modifies whitespace.
