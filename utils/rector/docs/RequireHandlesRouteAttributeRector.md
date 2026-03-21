# RequireHandlesRouteAttributeRector

Require `#[HandlesRoute]` on every class in `Controllers/` so the router discovers all handlers via reflection.

**Category:** Controller Conventions
**Mode:** `warn`
**Auto-fix:** No

## Rationale

The route registrar discovers controllers by reflecting `#[HandlesRoute]` at boot time. A controller missing this attribute is invisible to the router and will never handle requests. This rule prevents drift between the Controllers directory and the routing system by flagging any controller or handler class that lacks the attribute.

## What It Detects

Any class whose name ends in `Controller` or `Handler` (configurable) within the controllers namespace that does not carry `#[HandlesRoute]`.

## Transformation

### In `auto` mode

No-op — this rule is warn-only.

### In `warn` mode

```
// TODO: [RequireHandlesRouteAttributeRector] Attributes define properties — LoginController in Controllers/ is missing #[HandlesRoute]. Every controller must declare which route it handles so the router can discover it via reflection. See: utils/rector/docs/RequireHandlesRouteAttributeRector.md
```

Stale TODO comments are automatically removed when the attribute is added.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'auto'` is a no-op; `'warn'` adds a TODO comment |
| `attributeClass` | `string` | `'HandlesRoute'` | FQCN or short name of the required attribute |
| `controllerSuffixes` | `string[]` | `['Controller', 'Handler']` | Class name suffixes that identify controllers |
| `controllersNamespace` | `string` | `''` | If set, only flags classes in this namespace |

## Example

### Before

```php
readonly class LoginController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('login');
    }
}
```

### After

```php
// TODO: [RequireHandlesRouteAttributeRector] Attributes define properties — LoginController in Controllers/ is missing #[HandlesRoute]. Every controller must declare which route it handles so the router can discover it via reflection. See: utils/rector/docs/RequireHandlesRouteAttributeRector.md
readonly class LoginController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('login');
    }
}
```

## Resolution

When you see the TODO comment from this rule:

1. Determine which `Route` enum case this controller handles
2. Add `#[HandlesRoute(Route::case)]` to the class
3. The router will discover and wire the controller automatically

## Related Rules

- [`ForbidHttpMethodBranchingInControllerRector`](ForbidHttpMethodBranchingInControllerRector.md) — enforces separate handler methods per HTTP verb
- [`RequireSpecificResponseReturnTypeRector`](RequireSpecificResponseReturnTypeRector.md) — enforces concrete response types in controllers
- [`RequirePersistsOnTableReferenceRector`](RequirePersistsOnTableReferenceRector.md) — requires `#[Persists]` when importing table classes
