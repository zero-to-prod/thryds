# UseClassConstArrayKeyForDataModelRector

Adds string class constants for each property on a `DataModel` class, then replaces bare string keys in `::from()` call-site arrays with those constants.

**Category:** DataModel & ViewModel
**Mode:** `auto` or `warn` (configurable)
**Auto-fix:** Yes

## Rationale

The `DataModel::from(array $data)` factory populates properties by matching array keys to property names. Using bare strings like `'username'` at call sites is fragile — renaming a property silently breaks all callers, and there is no way to navigate from call site to declaration. The convention is:

- Each property gets a companion string constant (`public const string username = 'username';`) with a `@see $username` docblock so IDEs link constant → property.
- All `::from([...])` call sites use `ClassName::propertyName =>` instead of a string key.

This makes refactoring safe (a rename refactor can find the constant, not a string), enables IDE go-to-definition, and is consistent with the project-wide "constants name things" principle.

## What It Detects

**On the class itself:** A class using the `Zerotoprod\DataModel\DataModel` trait that has a property without a corresponding class constant.

**At call sites:** A `SomeClass::from([...])` call where any array key is a plain string that corresponds to a known property of that class.

## Transformation

### In `auto` mode

On the class: a `public const string <propName> = '<propName>';` constant with a `/** @see $<propName> */` docblock is inserted immediately before the property.

At call sites: string keys are replaced with class constant fetches.

```php
// Before
class UserProfile
{
    use DataModel;
    public string $email;
}
$UserProfile = UserProfile::from(['email' => 'john@example.com']);

// After
class UserProfile
{
    use DataModel;
    /** @see $email */
    public const string email = 'email';
    public string $email;
}
$UserProfile = UserProfile::from([\UserProfile::email => 'john@example.com']);
```

### In `warn` mode

A TODO comment is prepended to the property (on the class) or the `::from()` call (at call sites). No constants or replacements are made.

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `message` | `string` | `''` | TODO comment text (used in `warn` mode) |

**In `rector.php`:**
```php
$rectorConfig->ruleWithConfiguration(UseClassConstArrayKeyForDataModelRector::class, [
    'mode' => 'auto',
]);
```

## Example

### Before
```php
use Zerotoprod\DataModel\DataModel;

class UserProfile
{
    use DataModel;

    public const string username = 'username';
    public string $username;

    public string $email;
}

$UserProfile = UserProfile::from([
    'username' => 'john',
    'email' => 'john@example.com',
]);
```

### After
```php
use Zerotoprod\DataModel\DataModel;

class UserProfile
{
    use DataModel;

    public const string username = 'username';
    public string $username;
    /** @see $email */
    public const string email = 'email';

    public string $email;
}

$UserProfile = UserProfile::from([
    \UserProfile::username => 'john',
    \UserProfile::email => 'john@example.com',
]);
```

## Resolution

When you see the TODO comment from this rule:
1. Add `public const string <propName> = '<propName>';` to the DataModel class for each flagged property.
2. Replace string keys in all `::from([...])` calls with `ClassName::<propName>`.
3. Remove the TODO comments.

## Related Rules

- [`RequireMethodAnnotationForDataModelRector`](RequireMethodAnnotationForDataModelRector.md) — adds a `@method static self from(array{...})` docblock driven by the same property list
- [`ReplaceShortClassNameWithViewKeyRector`](ReplaceShortClassNameWithViewKeyRector.md) — replaces string/function-call array keys with the `view_key` constant
- [`ReplaceFullyQualifiedNameRector`](ReplaceFullyQualifiedNameRector.md) — redirects `use Zerotoprod\DataModel\DataModel` to the project-local alias
