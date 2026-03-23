# RouteOperationRequiredRector

Requires at least one `#[RouteOperation]` on every `Route` enum case so the
inventory graph can emit HTTP methods for every route.

**Category:** Graph completeness
**Mode:** `warn`
**Auto-fix:** No — the HTTP method and operation description are meaningful decisions
that cannot be generated automatically.

## Rationale

The inventory graph reads `$Route->operations()` to populate the `methods` field on
route nodes. A case without `#[RouteOperation]` causes `operations()` to return an
empty array, leaving the graph with no HTTP method for that route. An AI agent
cannot reason about whether a route is a read or write operation without this.

`#[RouteOperation]` is the single entry point for defining a route — it carries the
HTTP method, operation description, handler strategy, and optional resource-level
properties (info, controller, view). A missing attribute means both the graph
and the route manifest are incomplete for that case.

## What It Detects

A `Route` enum case that has zero `#[RouteOperation]` attributes:

```php
enum Route: string
{
    case about = '/about';  // ← flagged: no #[RouteOperation]
}
```

A case with multiple operations (e.g. GET + POST) is valid — at least one is all
that is required:

```php
#[RouteOperation(HttpMethod::GET,  'Render login form',       HandlerStrategy::form, info: 'Login', controller: LoginController::class, view: View::login)]
#[RouteOperation(HttpMethod::POST, 'Handle login submission', HandlerStrategy::validated)]
case login = '/login';  // ← fine
```

## Transformation

### In `warn` mode

A TODO comment is prepended to the offending case:

```php
// TODO: [RouteOperationRequiredRector] Route case 'about' must declare at least one #[RouteOperation] so the inventory graph can emit HTTP methods for this route.
case about = '/about';
```

The comment is automatically removed the next time Rector runs once the attribute
is present.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enumClass` | `string` | `''` | FQCN of the enum to inspect. Empty string matches all enums. |
| `attributeClass` | `string` | `'RouteOperation'` | Short or fully-qualified attribute class name to require. |
| `mode` | `string` | `'warn'` | Only `'warn'` is supported — adds a TODO comment. |
| `message` | `string` | see source | TODO comment text. Supports `%s` for the case name. |

## Example

### Before

```php
enum Route: string
{
    #[RouteOperation(HttpMethod::GET, 'Marketing home page', HandlerStrategy::static_view, info: 'Home', view: View::home)]
    case home = '/';

    case about = '/about';
}
```

### After

```php
enum Route: string
{
    #[RouteOperation(HttpMethod::GET, 'Marketing home page', HandlerStrategy::static_view, info: 'Home', view: View::home)]
    case home = '/';

    // TODO: [RouteOperationRequiredRector] Route case 'about' must declare at least one #[RouteOperation] so the inventory graph can emit HTTP methods for this route.
    case about = '/about';
}
```

## Resolution

When you see the TODO comment from this rule:

1. Decide the HTTP method(s) for the route — consult the route's purpose.
2. Add one `#[RouteOperation(HttpMethod::<METHOD>, '<operation description>', HandlerStrategy::<strategy>, info: '<route name>')]`
   per supported method above the enum case.
3. Run `./run fix:rector` — the TODO comment is removed automatically once the
   attribute is present.
