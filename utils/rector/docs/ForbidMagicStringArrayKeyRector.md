# ForbidMagicStringArrayKeyRector

Flags raw string literals used as array keys or array dimension fetches, requiring them to be replaced with named class constants.

**Category:** Magic String Elimination
**Mode:** `warn`
**Auto-fix:** No

## Rationale

The project principle "Constants name things" means every string key that acts as a semantic identifier must have a name attached to it. Magic string keys like `'cache'` or `'APP_ENV'` scatter their meaning throughout the codebase — refactoring, searching, or understanding intent requires hunting every usage site. A class constant gives the value a canonical home, documents its purpose at declaration, and makes the key greppable. Without this rule, array keys silently diverge across call sites and the codebase loses its self-documenting quality.

Calls to excluded classes (e.g., `Log::error`) are intentionally exempt because those context arrays have their own key-constant enforcement handled by dedicated logging rules.

## What It Detects

- Array literals with string keys: `['cache' => '/tmp']`
- Array dimension fetches with string subscripts: `$_ENV['APP_ENV']`, `$_SERVER['SERVER_NAME']`

The rule does not flag array items passed to method or static calls on classes listed in `excludedClasses`.

## Transformation

### In `auto` mode

No transformation is applied — `auto` mode is a no-op for this rule. Only `warn` mode is active.

### In `warn` mode

A `// TODO` comment is prepended to each offending array item or to the statement containing an array dimension fetch. The comment text is configurable via `message`, with `%s` replaced by the key value.

Project-configured comment (array item):
```
// TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value '%s' on the appropriate class.
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment; `'auto'` is a no-op |
| `message` | `string` | `"TODO: Replace magic string key '%s' with a class constant"` | Comment template; `%s` is replaced with the key value |
| `excludedClasses` | `string[]` | `[]` | Short class names whose method/static call array arguments are skipped |

**Project configuration (`rector.php`):**
```php
$rectorConfig->ruleWithConfiguration(ForbidMagicStringArrayKeyRector::class, [
    'excludedClasses' => [Log::class],
    'mode' => 'warn',
    'message' => "TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value '%s' on the appropriate class.",
]);
```

## Example

### Before (array item key)
```php
$options = [
    'cache' => '/tmp',
    'debug' => true,
];
```

### After (array item key)
```php
$options = [
    // TODO: Replace magic string key 'cache' with a class constant
    'cache' => '/tmp',
    // TODO: Replace magic string key 'debug' with a class constant
    'debug' => true,
];
```

### Before (array dimension fetch)
```php
$value = $_ENV['APP_ENV'];
$name = $_SERVER['SERVER_NAME'];
```

### After (array dimension fetch)
```php
// TODO: Replace magic string key 'APP_ENV' with a class constant
$value = $_ENV['APP_ENV'];
// TODO: Replace magic string key 'SERVER_NAME' with a class constant
$name = $_SERVER['SERVER_NAME'];
```

## Resolution

When you see the TODO comment from this rule:
1. Identify which class owns the meaning of this key (the class that defines or consumes the data structure).
2. Add `public const string KEY_NAME = 'key_value';` to that class.
3. Replace the string literal with `ClassName::KEY_NAME`.

## Related Rules

- [`RequireConstForRepeatedArrayKeyRector`](RequireConstForRepeatedArrayKeyRector.md) — flags string array keys repeated 2+ times in the same file, prompting extraction to a constant
