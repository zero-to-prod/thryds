# Fix: check-blade-push.php — Externalize Hardcoded Path and Namespace

## Script

`scripts/check-blade-push.php`

## Violations

### 1. Hardcoded component template directory (line 22)

```php
$template_dir = __DIR__ . '/../templates/components';
```

### 2. Hardcoded namespace import (line 20)

```php
use ZeroToProd\Thryds\Blade\Component;
```

## Fix

1. Load `blade-config.yaml` (shared with other Blade lint scripts).

2. Replace hardcoded values:

```php
$config = Yaml::parseFile(__DIR__ . '/../blade-config.yaml');
$template_dir = __DIR__ . '/../' . $config['component_dir'];
$componentClass = $config['namespaces']['component'];

foreach ($componentClass::cases() as $component) {
    // ...existing logic
}
```

## Constraints

- The Component enum is used for its `::cases()` method — the config provides its FQCN.
- Run `./run check:all` to verify no regressions.
