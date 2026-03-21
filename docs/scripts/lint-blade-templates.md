# Fix: lint-blade-templates.php — Externalize Hardcoded Path, Layouts, and Namespaces

## Script

`scripts/lint-blade-templates.php`

## Violations

### 1. Hardcoded template directory (line 28)

```php
$template_dir = __DIR__ . '/../templates';
```

### 2. Hardcoded known layouts (line 32)

```php
$known_layouts = ['base'];
```

### 3. Hardcoded namespace imports (lines 25-26)

```php
use ZeroToProd\Thryds\Blade\Component;
use ZeroToProd\Thryds\Blade\View;
```

The script directly references project-specific enum classes.

## Fix

1. Load `blade-config.yaml` (shared with other Blade lint scripts).

2. Replace hardcoded values:

```php
$config = Yaml::parseFile(__DIR__ . '/../blade-config.yaml');
$template_dir = __DIR__ . '/../' . $config['template_dir'];
$known_layouts = $config['known_layouts'];
$viewClass = $config['namespaces']['view'];
$componentClass = $config['namespaces']['component'];

$view_values = array_map(static fn($v) => $v->value, $viewClass::cases());
$component_values = array_map(static fn($c) => $c->value, $componentClass::cases());
```

## Constraints

- The enum classes (`View`, `Component`) are used for their `::cases()` method — the config provides their FQCNs, but the classes must still exist and be autoloadable.
- Run `./run check:all` to verify no regressions.
