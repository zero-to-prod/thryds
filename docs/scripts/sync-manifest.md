# Fix: sync-manifest.php — Externalize Hardcoded Namespaces in Scaffold Functions

## Script

`scripts/sync-manifest.php`

## Violations

### 1. Hardcoded namespaces in scaffold templates

Each `scaffold*()` function generates PHP files with hardcoded `ZeroToProd\Thryds\*` namespaces:

- `scaffoldView()` (line 81): `use ZeroToProd\Thryds\ViewModels\*`
- `scaffoldComponent()`: no namespace violation (template-only)
- `scaffoldViewModel()` (line 135): `namespace ZeroToProd\Thryds\ViewModels`
- `scaffoldTest()` (line 182): `namespace ZeroToProd\Thryds\Tests\Integration`
- `scaffoldController()` (line 248): `namespace ZeroToProd\Thryds\Controllers`

### 2. Hardcoded output directories

- `templates/` (line 71)
- `templates/components/` (line 97)
- `src/ViewModels/` (line 118)
- `tests/Integration/` (line 163)
- `src/Controllers/` (line 208)

## Fix

1. Create `scaffold-config.yaml` at the project root:

```yaml
namespaces:
  viewmodels: ZeroToProd\Thryds\ViewModels
  tests_integration: ZeroToProd\Thryds\Tests\Integration
  controllers: ZeroToProd\Thryds\Controllers
directories:
  templates: templates
  components: templates/components
  viewmodels: src/ViewModels
  tests_integration: tests/Integration
  controllers: src/Controllers
attributes:
  viewmodel: ZeroToProd\Thryds\Attributes\ViewModel
  data_model: ZeroToProd\Thryds\Attributes\DataModel
  handles_route: ZeroToProd\Thryds\Attributes\HandlesRoute
  covers_route: ZeroToProd\Thryds\Attributes\CoversRoute
  persists: ZeroToProd\Thryds\Attributes\Persists
  redirects_to: ZeroToProd\Thryds\Attributes\RedirectsTo
route_class: ZeroToProd\Thryds\Routes\Route
```

2. Pass the config into each scaffold function and use its values for namespace/path generation.

## Constraints

- The scaffold functions are called from the main loop — pass config as an additional parameter.
- Generated code must produce the same structure.
- Run `./run check:all` to verify no regressions.
