# Fix: make-rector-rule.php — Externalize Hardcoded Namespace and Paths

## Script

`scripts/make-rector-rule.php`

## Violations

### 1. Hardcoded namespace (lines 95, 154, 188)

```php
namespace Utils\Rector\Rector;
namespace Utils\Rector\Tests\{$rule_name};
```

### 2. Hardcoded output paths (lines 79, 307-325)

```php
$rule_path   = $base_dir . '/utils/rector/src/' . $rule_name . '.php';
$test_dir    = $base_dir . '/utils/rector/tests/' . $rule_name;
$docs_dir    = $base_dir . '/utils/rector/docs';
```

### 3. Hardcoded rector.php path (line 341)

```php
$rector_path = $base_dir . '/rector.php';
```

## Fix

1. Create `rector-scaffold-config.yaml` at the project root:

```yaml
rule_namespace: Utils\Rector\Rector
test_namespace: Utils\Rector\Tests
rule_dir: utils/rector/src
test_dir: utils/rector/tests
docs_dir: utils/rector/docs
rector_config: rector.php
```

2. In the script, load the config and replace all hardcoded values:

```php
$config = Yaml::parseFile($base_dir . '/rector-scaffold-config.yaml');
$rule_path = $base_dir . '/' . $config['rule_dir'] . '/' . $rule_name . '.php';
$test_dir  = $base_dir . '/' . $config['test_dir'] . '/' . $rule_name;
$docs_dir  = $base_dir . '/' . $config['docs_dir'];
$rector_path = $base_dir . '/' . $config['rector_config'];
```

3. Use `$config['rule_namespace']` and `$config['test_namespace']` in the generated file templates.

## Constraints

- The generated file structure (rule, test, config, fixture, doc) must remain the same.
- The rector.php appending logic is generic — just parameterize the path.
- Add `require __DIR__ . '/../vendor/autoload.php';` if not already present (needed for Yaml).
- Run `./run check:all` to verify no regressions.
