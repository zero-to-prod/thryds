# RequireViewEnumInMakeCallRector

Replaces string literals in Blade `make()` calls with the corresponding `View` enum case value expression.

**Category:** Route Safety (View Safety)
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (in `auto` mode)

## Rationale

View template names are magic strings that are invisible to static analysis and can silently break when templates are renamed. The project uses a `View` backed enum where every case value corresponds to a template name. Requiring `View::case->value` in `make()` calls ensures that:

- The compiler catches references to non-existent view names.
- Renaming a template requires updating the enum, not grep-searching strings.
- The full set of views is enumerable from code.

The rule uses PHP reflection to build a value-to-case map from the live enum at analysis time, so it only flags strings that actually match a known enum case.

## What It Detects

A `->make()` call (or the configured method name) that has a named argument matching `paramName` whose value is a string literal that corresponds to a `View` enum case backing value.

Strings that do not match any enum case are left alone (they may be invalid template names, which is a separate concern).

## Transformation

### In `auto` mode

Replaces the string literal with a fully-qualified `View::caseName->value` property fetch expression in place.

### In `warn` mode

Prepends a TODO comment to the `make()` call. The comment names the enum case and the original string.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enumClass` | `string` | `''` | FQCN of the View backed enum |
| `methodName` | `string` | `'make'` | Method name to inspect |
| `paramName` | `string` | `'view'` | Named argument name that holds the view string |
| `mode` | `string` | `'warn'` | `'auto'` to rewrite in place, `'warn'` to add a TODO comment |
| `message` | `string` | See source | Comment text (warn mode); `%s` placeholders receive the case name and original string |

Project configuration in `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RequireViewEnumInMakeCallRector::class, [
    'enumClass' => View::class,
    'methodName' => 'make',
    'paramName' => 'view',
    'mode' => 'auto',
    'message' => "TODO: [RequireViewEnumInMakeCallRector] Use View::%s->value instead of string '%s'.",
]);
```

## Example

### Before

```php
$Blade->make(view: 'home');
```

### After

```php
$Blade->make(view: \App\Helpers\View::home->value);
```

## Resolution

When you see the TODO comment from this rule (in `warn` mode):

1. Look up the enum case shown in the comment (e.g. `View::home`).
2. Replace the string argument with `View::home->value`.
3. If no matching case exists, add the template to the `View` enum first.
4. Run `./run check:all` to confirm no remaining violations.

## Related Rules

- [`ForbidHardcodedRouteStringRector`](ForbidHardcodedRouteStringRector.md) — analogous rule for route pattern strings used outside of `map()`
- [`RequireRouteEnumInMapCallRector`](RequireRouteEnumInMapCallRector.md) — analogous rule that enforces enum usage in route registration
