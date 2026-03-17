# Rector Rule: RequireViewEnumInMakeCallRector

**ADR-007 leg:** Enums limit choices

## Problem

After converting `View` from a constants class to an enum (migration #5 in constants-enums-consolidation), the `StringArgToClassConstRector` View mapping was removed because it generated `View::error` (a constant fetch) instead of `View::error->value` (an enum case with value access). Nothing now prevents `$Blade->make(view: 'home')` from being written — the string compiles and works, but bypasses the enum.

## Rule behavior

Detect `$Blade->make(view: 'string')` where the `view` argument is a string literal that matches a `View` enum case value. In `auto` mode, replace with `View::case->value`. In `warn` mode, add a TODO.

## Before / After

```php
// Before (bypasses enum)
$Blade->make(view: 'home');
$Blade->make(view: 'error', data: [...]);

// After (auto)
$Blade->make(view: View::home->value);
$Blade->make(view: View::error->value, data: [...]);
```

## Configuration

```php
// rector.php
$rectorConfig->ruleWithConfiguration(RequireViewEnumInMakeCallRector::class, [
    'enumClass' => \ZeroToProd\Thryds\Helpers\View::class,
    'methodName' => 'make',
    'paramName' => 'view',
    'mode' => 'auto',
    'message' => "TODO: [RequireViewEnumInMakeCallRector] Use View::%s->value instead of string '%s'.",
]);
```

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `enumClass` | `string` | (required) | FQN of the View enum |
| `methodName` | `string` | `'make'` | Method name to check |
| `paramName` | `string` | `'view'` | Named parameter to check |
| `mode` | `'auto'\|'warn'` | `'warn'` | `auto` replaces; `warn` adds TODO |
| `message` | `string` | See above | `sprintf`: `%1$s` = case name, `%2$s` = string value |

## Implementation

### Node types

`MethodCall` — for each call:

1. Check method name matches `methodName` config.
2. Find the `Arg` with `$arg->name->name === $paramName`.
3. If the arg value is a `String_`:
   a. Build a value→case map from `enumClass` (via `ReflectionEnum`).
   b. Look up the string value.
   c. If found, replace or warn.

### Auto mode replacement

Replace the `String_` node with:

```php
new PropertyFetch(
    new ClassConstFetch(
        new FullyQualified($enumClass),
        new Identifier($caseName)
    ),
    new Identifier('value')
)
```

This generates `\ZeroToProd\Thryds\Helpers\View::home->value`.

### Relationship to removed rule

This replaces the `StringArgToClassConstRector` mapping that was removed during migration #5. The key difference: this rule generates `View::case->value` (enum-aware), not `View::case` (constant fetch).

## Test structure

```
utils/rector/tests/RequireViewEnumInMakeCallRector/
├── RequireViewEnumInMakeCallRectorTest.php
├── config/
│   └── configured_rule.php
├── Support/
│   └── TestView.php          # Test-local backed enum
└── Fixture/
    ├── replaces_string_with_enum_value.php.inc
    ├── skips_already_enum_value.php.inc
    ├── skips_non_matching_method.php.inc
    └── skips_unknown_view_string.php.inc
```

### Fixture: `replaces_string_with_enum_value.php.inc`

```php
<?php

$Blade->make(view: 'home');

?>
-----
<?php

$Blade->make(view: \Utils\Rector\Tests\RequireViewEnumInMakeCallRector\TestView::home->value);

?>
```

### Fixture: `skips_already_enum_value.php.inc`

```php
<?php

$Blade->make(view: \Utils\Rector\Tests\RequireViewEnumInMakeCallRector\TestView::home->value);

?>
-----
<?php

$Blade->make(view: \Utils\Rector\Tests\RequireViewEnumInMakeCallRector\TestView::home->value);

?>
```

### Fixture: `skips_unknown_view_string.php.inc`

```php
<?php

$Blade->make(view: 'nonexistent');

?>
-----
<?php

$Blade->make(view: 'nonexistent');

?>
```

### Support: `TestView.php`

```php
<?php

namespace Utils\Rector\Tests\RequireViewEnumInMakeCallRector;

enum TestView: string
{
    case home = 'home';
    case error = 'error';
    case about = 'about';
}
```

### Config: `configured_rule.php`

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireViewEnumInMakeCallRector;

require_once __DIR__ . '/../Support/TestView.php';

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireViewEnumInMakeCallRector::class, [
        'enumClass' => 'Utils\\Rector\\Tests\\RequireViewEnumInMakeCallRector\\TestView',
        'methodName' => 'make',
        'paramName' => 'view',
        'mode' => 'auto',
    ]);
};
```

## Files affected at registration

| File | Change |
|------|--------|
| `rector.php` | Add import + `ruleWithConfiguration()` call |
| `utils/rector/src/RequireViewEnumInMakeCallRector.php` | New rule file |
| `utils/rector/tests/RequireViewEnumInMakeCallRector/` | New test directory |
