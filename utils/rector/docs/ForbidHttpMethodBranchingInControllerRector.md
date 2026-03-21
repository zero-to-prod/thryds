# ForbidHttpMethodBranchingInControllerRector

Flag if-statements in controllers that branch on the HTTP request method.

**Category:** Controller Conventions
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Controllers should not contain imperative branching on `getMethod()`. The route declares which HTTP methods it handles via `#[RouteOperation]` attributes, and the router dispatches to the correct handler. Method branching inside a controller duplicates routing logic, increases nesting depth, and violates the declarative programming principle.

## What It Detects

Any `if` / `elseif` condition inside a class ending in `Controller` that compares `$request->getMethod()` against:

- An `HttpMethod` enum `->value` property fetch (e.g., `HttpMethod::POST->value`)
- A string literal HTTP verb (`'GET'`, `'POST'`, `'PUT'`, `'PATCH'`, `'DELETE'`)

## Transformation

### In `auto` mode

No-op — this rule is warn-only.

### In `warn` mode

```
// TODO: [ForbidHttpMethodBranchingInControllerRector] Controllers must not branch on HTTP method — declare separate #[RouteOperation] handler methods and let the router dispatch. See: utils/rector/docs/ForbidHttpMethodBranchingInControllerRector.md
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'auto'` is a no-op; `'warn'` adds a TODO comment |
| `controllerSuffixes` | `string[]` | `['Controller']` | Class name suffixes that identify controllers |

## Example

### Before

```php
readonly class RegisterController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === HttpMethod::POST->value) {
            return new RedirectResponse('/login');
        }
        return new HtmlResponse('form');
    }
}
```

### After

```php
readonly class RegisterController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: [ForbidHttpMethodBranchingInControllerRector] Controllers must not branch on HTTP method — declare separate #[RouteOperation] handler methods and let the router dispatch. See: utils/rector/docs/ForbidHttpMethodBranchingInControllerRector.md
        if ($request->getMethod() === HttpMethod::POST->value) {
            return new RedirectResponse('/login');
        }
        return new HtmlResponse('form');
    }
}
```

## Resolution

When you see the TODO comment from this rule:

1. Split the controller into separate handler methods, one per HTTP method
2. Annotate each handler with the appropriate `#[RouteOperation]` attribute
3. Let the router dispatch to the correct method based on the request verb

## Related Rules

- [`RequireSpecificResponseReturnTypeRector`](RequireSpecificResponseReturnTypeRector.md) — enforces concrete response types in controllers
- [`RouteOperationRequiredRector`](RouteOperationRequiredRector.md) — requires `#[RouteOperation]` on route enum cases
