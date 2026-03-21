# Fix: make-requirement.php — Externalize Hardcoded Verifications, Namespaces, and Paths

## Script

`scripts/make-requirement.php`

## Violations

### 1. Hardcoded verification types (line 52)

```php
$valid_verifications = ['integration-test', 'unit-test', 'rector-rule', 'architecture', 'manual'];
```

### 2. Hardcoded test subdirectory map (line 122)

```php
$testable = ['integration-test' => 'Integration', 'unit-test' => 'Unit'];
```

### 3. Hardcoded test namespaces (lines 136, 140)

```php
$namespace = 'ZeroToProd\\Thryds\\Tests\\Integration';
$namespace = 'ZeroToProd\\Thryds\\Tests\\Unit';
```

### 4. Hardcoded base test class (line 137)

```php
$extends = 'IntegrationTestCase';
```

## Fix

1. Extend `requirements-config.yaml` (shared with `check-requirement-coverage.php`):

```yaml
testable_verifications:
  integration-test: Integration
  unit-test: Unit
all_verifications:
  - integration-test
  - unit-test
  - rector-rule
  - architecture
  - manual
test_namespaces:
  integration-test:
    namespace: ZeroToProd\Thryds\Tests\Integration
    extends: IntegrationTestCase
    extra_use: ""
  unit-test:
    namespace: ZeroToProd\Thryds\Tests\Unit
    extends: TestCase
    extra_use: "use PHPUnit\\Framework\\TestCase;"
```

2. In the script, load the config and replace all hardcoded values.

## Constraints

- The generated test file must have the same structure as today.
- `$valid_types` (`functional`, `non-functional`) is semantic and can remain inline.
- Run `./run check:all` to verify no regressions.
