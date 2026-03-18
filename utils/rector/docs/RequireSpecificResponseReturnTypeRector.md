# RequireSpecificResponseReturnTypeRector

Enforces that controller `__invoke()` methods declare their exact concrete response type instead of the generic `ResponseInterface`.

**Category:** Controller Conventions
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes (when concrete type is unambiguous)

## Rationale

Controllers that declare `ResponseInterface` as their return type obscure what they actually return. When every return statement in `__invoke()` instantiates the same concrete class (e.g. `HtmlResponse`), the generic interface adds no flexibility and hides useful information from IDEs, static analysers, and human readers. Declaring the specific type makes the contract explicit and removes a layer of indirection.

## What It Detects

A class within the configured `controllerNamespaces` that:
- Has a `__invoke()` method
- Declares `ResponseInterface` (or the configured `genericInterface`) as the return type

The rule then inspects all `return` statements in `__invoke()`. If every non-null return is a `new ConcreteClass(...)` of the same class, that class is used as the replacement type. If returns are mixed or cannot be statically resolved, a TODO comment is added instead.

## Transformation

### In `auto` mode

The `__invoke()` return type is rewritten to the resolved concrete class.

```php
// Before
class HomeController
{
    public function __invoke(): ResponseInterface
    {
        return new HtmlResponse('<h1>Hello</h1>');
    }
}

// After
class HomeController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('<h1>Hello</h1>');
    }
}
```

When the concrete type cannot be determined (mixed return types, variable returns), a TODO comment is added to the method regardless of mode.

### In `warn` mode

A TODO comment is prepended to the `__invoke()` method:

```
// TODO: [RequireSpecificResponseReturnTypeRector] Replace generic ResponseInterface return type with the specific response class actually returned (e.g. HtmlResponse or JsonResponse).
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `controllerNamespaces` | `string[]` | `[]` | Namespaces to restrict the rule to; empty means all classes |
| `genericInterface` | `string` | `'Psr\Http\Message\ResponseInterface'` | The return type to treat as generic |
| `mode` | `string` | `'warn'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | (see source) | TODO comment text |

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(RequireSpecificResponseReturnTypeRector::class, [
    'controllerNamespaces' => ['ZeroToProd\Thryds\Controllers'],
    'genericInterface' => 'Psr\Http\Message\ResponseInterface',
    'mode' => 'auto',
]);
```

## Example

### Before
```php
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function __invoke(): ResponseInterface
    {
        return new HtmlResponse('<h1>Hello</h1>');
    }
}
```

### After (warn mode — from test fixture)
```php
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    // TODO: [RequireSpecificResponseReturnTypeRector] Replace generic ResponseInterface return type with the specific response class actually returned (e.g. HtmlResponse or JsonResponse).
    public function __invoke(): ResponseInterface
    {
        return new HtmlResponse('<h1>Hello</h1>');
    }
}
```

## Resolution

When you see the TODO comment from this rule:
1. Identify all `return` statements in `__invoke()` and confirm they all return the same concrete type.
2. Replace `ResponseInterface` in the return type declaration with that concrete class (e.g. `HtmlResponse`).
3. Remove the now-unused `use Psr\Http\Message\ResponseInterface;` import if it is no longer referenced.
4. Remove the TODO comment.
5. If `__invoke()` genuinely returns multiple different response types, keep `ResponseInterface` and suppress this rule for that method.

## Related Rules

None directly. This rule is the sole controller convention rule in the current configuration.
