# ForbidClassInheritanceRector

Flags any class that uses `extends` to inherit from another class, and suggests replacing inheritance with PHP attributes and composition.

**Category:** Forbidden Constructs
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Class inheritance couples a subclass to the implementation details of its parent. When a parent changes, subclasses break silently. Attribute Oriented Programming (AOP) makes relationships explicit, discoverable, and enforceable without coupling. Properties belong on attributes, not on parent classes.

## What It Detects

Any `class Foo extends Bar` declaration where `Bar` is not in the built-in or configured allow list.

## Transformation

### In `auto` mode

No-op. This is a pure warn rule.

### In `warn` mode

Prepends a TODO comment to the offending class:

```
// TODO: [ForbidClassInheritanceRector] Inheritance couples classes to parent implementation — use PHP attributes and composition instead. See: utils/rector/docs/ForbidClassInheritanceRector.md
```

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'warn'` | `'warn'` adds a TODO comment; `'auto'` is a no-op |
| `message` | `string` | Built-in TODO | The TODO text prepended to flagged classes |
| `allowList` | `list<string>` | `[]` | Additional parent FQCNs to permit (merged with built-in list) |

### Built-in allow list

The following parent classes are always permitted:

- `PHPUnit\Framework\TestCase`
- `Rector\Rector\AbstractRector`
- `Rector\Testing\PHPUnit\AbstractRectorTestCase`
- `PhpParser\NodeVisitorAbstract`
- `PhpParser\PrettyPrinter\Standard`
- `Symfony\Component\Console\Command\Command`
- `Symfony\Component\EventDispatcher\EventSubscriberInterface`
- Standard PHP exception base classes (`Exception`, `RuntimeException`, `LogicException`, `InvalidArgumentException`, `BadMethodCallException`, `OverflowException`, `UnderflowException`, `OutOfRangeException`, `DomainException`, `LengthException`, `RangeException`, `UnexpectedValueException`, `OutOfBoundsException`)

The `allowList` config key appends to this list; it does not replace it.

## Example

### Before

```php
class UserService extends BaseService
{
    public function find(int $id): string
    {
        return '';
    }
}
```

### After

```php
// TODO: [ForbidClassInheritanceRector] Inheritance couples classes to parent implementation — use PHP attributes and composition instead. See: utils/rector/docs/ForbidClassInheritanceRector.md
class UserService extends BaseService
{
    public function find(int $id): string
    {
        return '';
    }
}
```

## Resolution

When you see the TODO comment from this rule:

1. **Composition** — inject the former parent as a constructor parameter instead of extending it.
2. **PHP Attributes** — declare behaviour with an attribute (e.g. `#[Loggable]`) so tooling can discover and enforce it without a runtime parent-child contract.
3. **Traits** — if the only reason for the parent was shared implementation, move that to a trait.
4. **Add to allowList** — if the parent is a framework/library class that genuinely cannot be avoided, add its FQCN to `allowList` in `rector.php`.

## Related Rules

- `ForbidInterfaceRector` — flags `interface` declarations for the same AOP reasoning.
