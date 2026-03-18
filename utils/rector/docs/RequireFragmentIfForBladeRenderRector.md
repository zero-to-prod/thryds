# RequireFragmentIfForBladeRenderRector

Flags `->make()->render()` chains that should use `->fragmentIf()` to support htmx partial page updates.

**Category:** Blade / htmx
**Mode:** `warn` only (no auto-fix)
**Auto-fix:** No

## Rationale

htmx sends requests with the `HX-Request` header to signal that it expects a partial HTML response. When a controller calls `$Blade->make(...)->render()`, it always returns the full rendered page — even on htmx partial requests. This causes htmx to receive and swap the entire page HTML instead of just the targeted fragment, breaking partial updates.

The correct pattern is `->fragmentIf($request->hasHeader(Header::hx_request), 'body')`, which returns the full page for normal requests and only the named fragment for htmx requests. This rule identifies every `->make()->render()` call site that has not yet been converted.

## What It Detects

`Expression` statements where:
- The expression is a `MethodCall` to `render()`.
- The receiver of `render()` is itself a `MethodCall` to `make()`.

In other words: `$anything->make(...)->render()`.

## Transformation

### In `warn` mode

A TODO comment is prepended to the statement:

```
// TODO: [RequireFragmentIfForBladeRenderRector] ->render() returns the full page. For htmx partial requests, use ->fragmentIf($request->hasHeader(Header::hx_request), 'body') instead.
```

This rule has no `auto` mode. The replacement requires the `$request` object and the fragment name, which are context-dependent and cannot be inferred automatically.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | Only `'warn'` is effective |
| `message` | `string` | see source | The exact TODO comment text (no `sprintf` placeholders) |

In `rector.php`:

```php
$rectorConfig->ruleWithConfiguration(RequireFragmentIfForBladeRenderRector::class, [
    'mode' => 'warn',
    'message' => "TODO: [RequireFragmentIfForBladeRenderRector] ->render() returns the full page. For htmx partial requests, use ->fragmentIf(\$request->hasHeader(Header::hx_request), 'body') instead.",
]);
```

## Example

### Before

```php
$Blade->make(view: 'home')->render();
```

### After

```php
// TODO: [RequireFragmentIfForBladeRenderRector] ->render() returns the full page. For htmx partial requests, use ->fragmentIf($request->hasHeader(Header::hx_request), 'body') instead.
$Blade->make(view: 'home')->render();
```

## Resolution

When you see the TODO comment from this rule:
1. Replace `->render()` with `->fragmentIf($request->hasHeader(Header::hx_request), 'body')`.
2. Ensure `$request` (a PSR-7 `ServerRequestInterface`) is available in scope — typically injected into the controller method.
3. Ensure a Blade fragment named `'body'` (or the appropriate fragment name) is defined in the view template using `@fragment('body') ... @endfragment`.
4. If this is not an htmx-aware endpoint and will never receive partial requests, you may suppress this rule for the specific call by adding a preceding comment that includes the rule marker string.

## Related Rules

- [`RequireEnumValueAccessRector`](RequireEnumValueAccessRector.md) — enforces `->value` on enum cases passed to string arguments (e.g., `Header::hx_request->value`)
