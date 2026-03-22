# RouteInfoRequiredRector

**Mode:** `warn` (adds `// TODO:` comment — no auto-fix; description content requires developer intent)

## Why

`scripts/list-inventory.php` calls `$Route->description()` on every `Route` enum case to populate the inventory graph's `description` field. That method reads the `#[RouteInfo]` attribute. A case without it causes the graph to emit an empty description, making the route invisible to intent-based queries (e.g. "where is auth handled?").

## What it does

Adds a `// TODO:` comment on any `Route` enum case that is missing `#[RouteInfo]`. When `#[RouteInfo]` is added, the comment is automatically removed on the next rector run.

## Configuration

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `enumClass` | `string` | `''` | FQCN or short name of the enum to check. Empty matches all enums. |
| `attributeClass` | `string` | `'RouteInfo'` | FQCN or short name of the required attribute. |
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

    // TODO: [RouteInfoRequiredRector] Route case 'about' must declare #[RouteInfo] so the inventory graph can emit a description for this route. See: utils/rector/docs/RouteInfoRequiredRector.md
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
