# Fix: inventory.php — Externalize Hardcoded Attribute Classes, Namespaces, and Paths

## Script

`scripts/inventory.php`

## Violations

### 1. Hardcoded attribute class imports (lines 19-34)

```php
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Attributes\CoversRoute;
use ZeroToProd\Thryds\Attributes\ExtendsLayout;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Attributes\Persists;
use ZeroToProd\Thryds\Attributes\Prop;
use ZeroToProd\Thryds\Attributes\ReceivesViewModel;
use ZeroToProd\Thryds\Attributes\RedirectsTo;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\Attributes\Table;
use ZeroToProd\Thryds\Attributes\UsesComponent;
use ZeroToProd\Thryds\Attributes\ViewModel;
use ZeroToProd\Thryds\Blade\Component;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Routes\Route;
```

### 2. Hardcoded controller discovery path and namespace (lines 53-56)

```php
$controllersDir = $projectRoot . 'src/Controllers';
$controllerFqcn = 'ZeroToProd\\Thryds\\Controllers\\' . $controllerClassName;
```

### 3. Hardcoded template directory (line 49)

```php
$templatesDir = $projectRoot . 'templates';
```

## Fix

1. Create `inventory-config.yaml` at the project root:

```yaml
template_dir: templates
controllers_dir: src/Controllers
controllers_namespace: ZeroToProd\Thryds\Controllers
attributes:
  handles_route: ZeroToProd\Thryds\Attributes\HandlesRoute
  covers_route: ZeroToProd\Thryds\Attributes\CoversRoute
  extends_layout: ZeroToProd\Thryds\Attributes\ExtendsLayout
  receives_viewmodel: ZeroToProd\Thryds\Attributes\ReceivesViewModel
  uses_component: ZeroToProd\Thryds\Attributes\UsesComponent
  view_model: ZeroToProd\Thryds\Attributes\ViewModel
  table: ZeroToProd\Thryds\Attributes\Table
  column: ZeroToProd\Thryds\Attributes\Column
  persists: ZeroToProd\Thryds\Attributes\Persists
  redirects_to: ZeroToProd\Thryds\Attributes\RedirectsTo
  route_operation: ZeroToProd\Thryds\Attributes\RouteOperation
  prop: ZeroToProd\Thryds\Attributes\Prop
  closed_set: ZeroToProd\Thryds\Attributes\ClosedSet
enums:
  route: ZeroToProd\Thryds\Routes\Route
  view: ZeroToProd\Thryds\Blade\View
  component: ZeroToProd\Thryds\Blade\Component
```

2. In the script, load the config and resolve all class references dynamically.

## Constraints

- This is a large script with extensive reflection logic. The class references are used in `getAttributes()` calls — dynamic class names work fine with `ReflectionClass::getAttributes($fqcn)`.
- Consider whether `attribute-graph.yaml` (which already externalizes similar references for `attribute-graph.php`) can be extended to serve both scripts.
- Run `./run check:all` to verify no regressions.
