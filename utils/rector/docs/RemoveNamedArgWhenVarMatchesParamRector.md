# RemoveNamedArgWhenVarMatchesParamRector

Remove a named argument label when the variable or class-constant name already matches the parameter name, making it redundant.

**Category:** Naming
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

Named arguments exist to clarify the mapping between a value and the parameter it satisfies. When the variable is already named after the parameter — `register(Router: $Router)` — the label adds noise without adding information. Removing it yields the cleaner `register($Router)` while preserving all the self-documenting benefit. The rule also applies to `ClassConstFetch` arguments in attributes (e.g. `#[ClosedSet(Domain: Domain::url_routes)]`), where the const class name matches the parameter name.

The rule is careful not to create a positional-after-named violation: it only removes a label when no preceding argument will remain named after all removals.

## What It Detects

A call or attribute where a named argument's label exactly matches the variable name (or the last segment of a `ClassName::const` class constant) passed as its value.

## Transformation

### In `auto` mode
Removes the `name:` label from qualifying arguments, leaving bare positional arguments.

### In `warn` mode
Adds the configured `message` as a `//` comment above the call (idempotent).

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to rewrite, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | Comment text used in `warn` mode |

Project config (`rector.php`): `mode => 'auto'`.

## Example

### Before
```php
RouteRegistrar::register(Router: $Router, Blade: $Blade);
doSomething(request: $request);
new HtmlResponse(html: $html);
```

### After
```php
RouteRegistrar::register($Router, $Blade);
doSomething($request);
new HtmlResponse($html);
```

### Before (attribute with class constant)
```php
#[ClosedSet(Domain: Domain::url_routes)]
enum Route: string {}
```

### After
```php
#[ClosedSet(Domain::url_routes)]
enum Route: string {}
```

## Related Rules

- [`AddNamedArgWhenVarMismatchesParamRector`](AddNamedArgWhenVarMismatchesParamRector.md) — the inverse: adds named labels when the variable name differs from the parameter name
