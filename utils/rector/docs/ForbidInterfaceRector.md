# ForbidInterfaceRector

Flags interface declarations and recommends PHP attributes as the AOP alternative.

**Category:** Forbidden Constructs
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Interfaces define implicit contracts through method signatures — implementations must satisfy the shape, but the intent is invisible to static tooling and the attribute graph. PHP attributes declare properties explicitly: they are discoverable via reflection, enforceable by Rector rules, composable without coupling, and visible in the attribute graph.

In an AOP codebase, the role that interfaces traditionally fill (marking capability, enforcing structure, enabling polymorphism) is better served by attributes:

| Interface pattern | AOP equivalent |
|---|---|
| Marker interface (`Loggable`) | `#[Loggable]` attribute |
| Strategy interface (`Resolver`) | `#[Resolves(Target)]` attribute + convention |
| Contract interface (`Repository`) | `#[PersistsWith(Driver)]` attribute |

## What It Detects

Any `interface` declaration not present in the configured `allowList`.

## Transformation

### In `warn` mode

Prepends a TODO comment to the interface declaration:

```
// TODO: [ForbidInterfaceRector] Interfaces define implicit contracts — use PHP attributes to declare properties explicitly. Attributes are discoverable, enforceable, and composable without coupling. See: utils/rector/docs/ForbidInterfaceRector.md
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'warn'` to add a TODO comment (`'auto'` is a no-op) |
| `allowList` | `list<string>` | `[]` | FQCNs of interfaces exempt from the rule |
| `message` | `string` | *(built-in)* | Override the TODO comment text |

## Example

### Before

```php
interface Loggable
{
    public function toLogContext(): array;
}
```

### After

```php
// TODO: [ForbidInterfaceRector] Interfaces define implicit contracts — use PHP attributes to declare properties explicitly. ...
interface Loggable
{
    public function toLogContext(): array;
}
```

## Resolution

When you see the TODO comment from this rule:

1. Identify what the interface communicates (capability, strategy, contract).
2. Create a PHP attribute that declares the same property explicitly.
3. Apply the attribute to the implementing classes.
4. Remove the interface and its `implements` clauses.
5. If the interface is load-bearing and cannot be migrated yet, add its FQCN to the `allowList` in `rector.php`.

## Related Rules

None yet.
