# Fix: lint-blade-components.php — Externalize Hardcoded Path and Tag Rules

## Script

`scripts/lint-blade-components.php`

## Violations

### 1. Hardcoded template directory (line 21)

```php
$template_dir = __DIR__ . '/../templates';
```

### 2. Hardcoded tag rules (lines 25-41)

```php
$tag_rules = [
    '/<button\b/' => ['rule' => 'raw-html-button', ...],
    '/<input\b/'  => ['rule' => 'raw-html-input', ...],
    '/<div\s[^>]*role\s*=\s*["\']alert["\']/' => ['rule' => 'raw-html-alert', ...],
];
```

These rules are project-specific — different projects may have different component wrappers.

## Fix

1. Load `blade-config.yaml` (shared with `lint-blade-routes.php`, `lint-blade-templates.php`, `check-blade-push.php`).

2. Replace hardcoded values:

```php
$config = Yaml::parseFile(__DIR__ . '/../blade-config.yaml');
$template_dir = __DIR__ . '/../' . $config['template_dir'];

$tag_rules = [];
foreach ($config['tag_rules'] as $entry) {
    $tag_rules[$entry['pattern']] = [
        'rule'    => $entry['rule'],
        'message' => $entry['message'],
        'fix'     => $entry['fix'],
    ];
}
```

## Constraints

- The autoloader is already required.
- Preserve the `scanBladeFiles()` helper and component-directory skip logic.
- Run `./run check:all` to verify no regressions.
