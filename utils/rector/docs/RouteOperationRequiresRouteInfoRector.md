# RouteOperationRequiresRouteInfoRector

**Mode:** `warn` (adds `// TODO:` comment — no auto-fix; description content requires developer intent)

## Why

`#[RouteOperation]` and `#[RouteInfo]` are a required pair on every Route enum case.
`#[RouteOperation]` declares the HTTP method and operation description; `#[RouteInfo]`
declares the route-level description. The inventory graph needs both to emit a complete
route node. A case that declares what it *does* (`#[RouteOperation]`) but not what it
*is* (`#[RouteInfo]`) produces a route node with an empty description field.

## What it does

Adds a `// TODO:` comment on any Route enum case that has `#[RouteOperation]` but is
missing `#[RouteInfo]`. Cases with neither attribute are ignored — that is a separate
rule's concern. When `#[RouteInfo]` is added, the comment is automatically removed on
the next rector run.

## Configuration

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `enumClass` | `string` | `''` | FQCN or short name of the enum to check. Empty matches all enums. |
| `triggerAttributeClass` | `string` | `'RouteOperation'` | Attribute whose presence triggers the check. |
| `requiredAttributeClass` | `string` | `'RouteInfo'` | Attribute that must accompany the trigger. |
| `mode` | `string` | `'warn'` | Only `warn` is supported — description content cannot be generated. |
| `message` | `string` | see source | TODO comment text. Use `%s` for the case name. |

## Before

```php
enum Route: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';

    #[RouteOperation(HttpMethod::GET, 'Company information')]
    case about = '/about';
}
```

## After

```php
enum Route: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';

    // TODO: [RouteOperationRequiresRouteInfoRector] Route case 'about' declares #[RouteOperation] but is missing #[RouteInfo]. Both attributes are required together: #[RouteOperation] declares HTTP methods, #[RouteInfo] declares the route description. See: utils/rector/docs/RouteOperationRequiresRouteInfoRector.md
    #[RouteOperation(HttpMethod::GET, 'Company information')]
    case about = '/about';
}
```

## Resolution

Add `#[RouteInfo('description')]` above the flagged case:

```php
#[RouteInfo('Company and product information')]
#[RouteOperation(HttpMethod::GET, 'Company information')]
case about = '/about';
```

Then re-run `./run fix:rector` — the TODO comment is removed automatically.
