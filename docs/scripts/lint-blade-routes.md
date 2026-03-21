# Fix: lint-blade-routes.php — Externalize Hardcoded Template Path

## Script

`scripts/lint-blade-routes.php`

## Violation

**Line 27** — Hardcoded template directory:

```php
$template_dir = __DIR__ . '/../templates';
```

## Fix

1. Create `blade-config.yaml` at the project root (shared across all Blade lint scripts):

```yaml
template_dir: templates
component_dir: templates/components
namespaces:
  view: ZeroToProd\Thryds\Blade\View
  component: ZeroToProd\Thryds\Blade\Component
known_layouts:
  - base
tag_rules:
  - pattern: '/<button\b/'
    rule: raw-html-button
    message: 'raw <button> tag'
    fix: 'Use <x-button> component instead'
  - pattern: '/<input\b/'
    rule: raw-html-input
    message: 'raw <input> tag'
    fix: 'Use <x-input> component instead'
  - pattern: '/<div\s[^>]*role\s*=\s*["\x27]alert["\x27]/'
    rule: raw-html-alert
    message: 'raw <div role="alert"> tag'
    fix: 'Use <x-alert> component instead'
```

2. In the script, load the config:

```php
$config = Yaml::parseFile(__DIR__ . '/../blade-config.yaml');
$template_dir = __DIR__ . '/../' . $config['template_dir'];
```

3. Add `require __DIR__ . '/../vendor/autoload.php';` (currently not required — needed for Yaml).

## Constraints

- This script currently has no autoloader require — adding it is necessary for Yaml parsing.
- The regex patterns and violation detection logic are generic and can stay as-is.
- Run `./run check:all` to verify no regressions.
