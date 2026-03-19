# DetectStaleCodeReferencesRector

Detect stale `@see` / `@link` references to PHP class members (methods, constants, properties) that no longer exist.

**Category:** Documentation correctness
**Mode:** `warn` only (cannot auto-fix — the correct replacement is unknown)
**Auto-fix:** No

## Rationale

Docblock tags like `@see RouteRegistrar::register()` and `@link Handler::process()` become stale when the referenced method is renamed or removed. The comment stays behind, pointing readers to code that no longer exists. This rule surfaces such references during static analysis so they can be updated or removed.

## What It Detects

Scans `@see`, `@link`, and inline `{@link}` tags within docblocks on:
- Classes
- Methods
- Functions
- Properties
- Class constants

A reference is flagged when:
1. The class name is resolvable (via a `use` statement, the current namespace, or a fully-qualified name), **and**
2. The referenced member (method, constant, or property) does not exist on that class.

References to class names that cannot be resolved are silently skipped to avoid false positives.

## Transformation

### In `warn` mode

A `// TODO` comment is prepended to the annotated node. The comment is idempotent — it is not added again if already present.

```
// TODO: [DetectStaleCodeReferencesRector] Comment references '%s' which does not exist. Verify or remove.
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `warn` | Must be `warn`. Auto-fix is not supported. |
| `message` | `string` | See above | Format string for the TODO comment. Must contain one `%s` placeholder for the stale reference. |

## Example

### Before

```php
use App\Routes\RouteRegistrar;

class Dispatcher
{
    /**
     * @see RouteRegistrar::register()
     */
    public function dispatch(): void {}
}
```

### After

```php
use App\Routes\RouteRegistrar;

class Dispatcher
{
    // TODO: [DetectStaleCodeReferencesRector] Comment references 'RouteRegistrar::register' which does not exist. Verify or remove.
    /**
     * @see RouteRegistrar::register()
     */
    public function dispatch(): void {}
}
```

## Resolution

When you see the TODO comment from this rule:

1. Find the method, constant, or property that was intended — it may have been renamed.
2. Update the `@see` / `@link` tag to point to the current name, or remove the tag if the reference is no longer relevant.

## Limitations

- Only `@see`, `@link`, and `{@link}` tags are scanned — prose mentions of `ClassName::method()` in comment text are not checked.
- The class name must be resolvable via a `use` statement, the current file's namespace, or be fully-qualified (e.g. `\App\Foo\Bar::method()`). Global classes like `Exception` must be explicitly imported to be checked.

## Related Rules

- `ValidateChecklistPathsRector` — validates file path references in `#[Attribute]` strings
