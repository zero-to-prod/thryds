# StringArgToClassConstRector

Replaces string literal named arguments with class constant fetches, and creates missing constants on the target class automatically.

**Category:** Code Quality / Magic String Elimination
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

Constants name things. When a string is passed as a named argument to a known method, that string represents a named concept that belongs in a constant on a specific class. Replacing the string with `ClassName::CONST_NAME` makes the value discoverable, renameable, and statically analysable. The rule also handles the bootstrap step: if the constant does not yet exist, it adds it to the target class file.

## What It Detects

`MethodCall` nodes where a named argument matching a configured `paramName` on a configured `methodName` has a string literal value.

```php
$Blade->make(view: 'error', data: []);
```

## Transformation

### In `auto` mode

1. Locates the class file via PHPStan reflection.
2. If the constant does not exist, inserts `public const string <constName> = '<value>';` before the closing brace of the class.
3. Replaces the string argument with `\Full\Class\Name::constName`.

Dots in the string value are converted to underscores to form a valid PHP identifier (e.g. `'components.button'` → `components_button`).

### In `warn` mode

Adds a comment above the method call.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mappings` | `array` | `[]` | List of `{class, methodName, paramName}` triples defining which string args to replace |
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Each mapping entry:
| Key | Type | Description |
|-----|------|-------------|
| `class` | `string` | Fully qualified class name that should hold the constants |
| `methodName` | `string` | Method name to match on the call |
| `paramName` | `string` | Named argument whose value is the string to replace |

Project config (`rector.php`):
```php
$rectorConfig->ruleWithConfiguration(StringArgToClassConstRector::class, [
    'mappings' => [],
    'mode' => 'auto',
]);
```

## Example

### Before
```php
$Blade->make(view: 'error', data: []);
$Blade->make(view: 'components.button', data: []);
$Blade->make(view: 'layouts.app', data: ['user' => $user]);
```

### After
```php
$Blade->make(view: \App\View::error, data: []);
$Blade->make(view: \App\View::components_button, data: []);
$Blade->make(view: \App\View::layouts_app, data: ['user' => $user]);
```

And `App\View` will have received:
```php
public const string error = 'error';
public const string components_button = 'components.button';
public const string layouts_app = 'layouts.app';
```

## Related Rules

- [`SuggestDuplicateStringConstantRector`](SuggestDuplicateStringConstantRector.md) — flags repeated string literals that should also be extracted to a constant
