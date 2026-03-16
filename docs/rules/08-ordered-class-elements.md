# ordered_class_elements

## Tool

PHP CS Fixer (built-in rule)

## What it does

Automatically reorders class elements into a consistent structure: traits, constants,
properties, constructor, then methods — sorted by visibility. Applied on
`php-cs-fixer fix`.

## Why it matters

When class elements follow a predictable order, an agent always knows where to look.
Constants are at the top, the constructor is after properties, public methods come before
private ones. The agent never has to scan an entire class to find what it needs.

## Configuration

```php
// .php-cs-fixer.php
return (new Config())
    ->setRules([
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public_static',
                'property_protected_static',
                'property_private_static',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
                'method_public',
                'method_protected',
                'method_private',
            ],
            'sort_algorithm' => 'none',
            'case_sensitive' => false,
        ],
    ]);
```

### Options

| Option | Type | Default | Description |
|---|---|---|---|
| `order` | `string[]` | *(see above)* | Ordered list of element types. Elements not listed are placed at the end. |
| `sort_algorithm` | `'none'` \| `'alpha'` | `'none'` | How to sort elements of the same type. `'alpha'` sorts alphabetically by name. |
| `case_sensitive` | `bool` | `false` | Whether alphabetical sorting is case-sensitive |

### Available element types

`use_trait`, `case`, `constant`, `constant_public`, `constant_protected`, `constant_private`,
`property`, `property_static`, `property_public`, `property_protected`, `property_private`,
`property_public_static`, `property_protected_static`, `property_private_static`,
`property_public_readonly`, `property_protected_readonly`, `property_private_readonly`,
`construct`, `destruct`, `magic`, `phpunit`,
`method`, `method_abstract`, `method_static`, `method_public`, `method_protected`,
`method_private`, `method_public_static`, `method_protected_static`, `method_private_static`,
`method_public_abstract`, `method_protected_abstract`

Custom method pinning: `method:methodName` (e.g., `method:__invoke`) to place a specific
method at a fixed position.

## Before / After

### Before

```php
class UserService
{
    private function hashPassword(string $password): string { /* ... */ }

    public const TABLE = 'users';

    public function __construct(private readonly DB $DB) {}

    private string $cache;

    public function find(int $id): User { /* ... */ }
}
```

### After

```php
class UserService
{
    public const TABLE = 'users';

    private string $cache;

    public function __construct(private readonly DB $DB) {}

    public function find(int $id): User { /* ... */ }

    private function hashPassword(string $password): string { /* ... */ }
}
```

## Implementation notes

- Built-in CS Fixer rule — no custom code required.
- Add to the `setRules()` array in `.php-cs-fixer.php`.
- `sort_algorithm => 'none'` preserves the author's ordering within each group.
  Use `'alpha'` only if strict alphabetical ordering is preferred.
- This rule is safe (not risky) — it only reorders declarations.
