# RequirePersistsOnTableReferenceRector

Flags controller classes that import a Tables-namespace class but are missing
a matching `#[Persists(TableClass::class)]` attribute, ensuring the inventory
graph always has a persistence edge for every write path.

**Category:** Graph completeness
**Mode:** `warn`
**Auto-fix:** No — only a developer can confirm whether the import implies persistence.

## Rationale

The inventory graph reads `#[Persists]` attributes via reflection to emit
`controller → model` edges. A controller that uses a Table class for
persistence but omits `#[Persists]` causes the graph to silently drop the
write-path edge. An AI agent cannot reason about what a controller persists
to without this annotation.

## What It Detects

A controller in the configured namespace that has a `use` import from the
tables namespace but no corresponding `#[Persists]` declaration:

```php
namespace App\Controllers;

use App\Tables\User;  // ← import detected, #[Persists] missing → flagged

class RegisterController
{
    public function __invoke(): void {}
}
```

A controller with a matching `#[Persists]` for every table import is fine:

```php
#[Persists(User::class)]  // ← covered
class RegisterController { ... }
```

## Transformation

### In `warn` mode

A TODO comment is prepended to the offending class:

```php
// TODO: [RequirePersistsOnTableReferenceRector] 'RegisterController' imports 'User' from the tables namespace but is missing #[Persists(User::class)].
class RegisterController { ... }
```

The comment is automatically removed the next time Rector runs once the
attribute is present.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `tablesNamespace` | `string` | `''` | Namespace prefix for table/model classes. |
| `attributeClass` | `string` | `'Persists'` | Short or fully-qualified `#[Persists]` attribute name. |
| `controllersNamespace` | `string` | `''` | Restrict detection to this namespace. Empty matches all classes. |
| `mode` | `string` | `'warn'` | Only `'warn'` is supported — adds a TODO comment. |
| `message` | `string` | see source | TODO text. Supports `%s` for: class name, table short name, table short name. |

## Resolution

When you see the TODO comment from this rule:

1. Confirm the controller writes to the flagged table class.
2. Add `#[Persists(TableClass::class)]` above the class declaration.
3. Run `./run fix:rector` — the TODO is removed automatically once the attribute is present.

## Related Rules

- `RouteOperationRequiredRector` — enforces `#[RouteOperation]` on every Route case.
