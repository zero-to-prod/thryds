# Fix: check-requirement-coverage.php — Externalize Hardcoded Validation Sets and Paths

## Script

`scripts/check-requirement-coverage.php`

## Violations

### 1. Hardcoded testable verification types (line 40)

```php
$testable = ['integration-test', 'unit-test'];
```

### 2. Hardcoded test subdirectory map (line 41)

```php
$test_subdir = ['integration-test' => 'Integration', 'unit-test' => 'Unit'];
```

### 3. Hardcoded authority types (line 43)

```php
$known_authority_types = ['rfc', 'w3c', 'ietf-draft', 'iso', 'ecma'];
```

### 4. Hardcoded link rels (line 44)

```php
$known_link_rels = ['adr', 'issue', 'pr', 'prior-art', 'discussion'];
```

### 5. Hardcoded Rector rule path (line 137)

```php
$file = $base_dir . '/utils/rector/src/' . $rule . '.php';
```

## Fix

1. Create `requirements-config.yaml` at the project root:

```yaml
testable_verifications:
  integration-test: Integration
  unit-test: Unit
known_authority_types:
  - rfc
  - w3c
  - ietf-draft
  - iso
  - ecma
known_link_rels:
  - adr
  - issue
  - pr
  - prior-art
  - discussion
rector_rules_dir: utils/rector/src
tests_dir: tests
```

2. In the script, load the config and derive all sets from it:

```php
$config = Yaml::parseFile($base_dir . '/requirements-config.yaml');
$test_subdir = $config['testable_verifications'];
$testable = array_keys($test_subdir);
$known_authority_types = $config['known_authority_types'];
$known_link_rels = $config['known_link_rels'];
$rector_dir = $base_dir . '/' . $config['rector_rules_dir'];
```

3. Share `requirements-config.yaml` with `make-requirement.php`.

## Constraints

- The `parse_requirements()` function is generic — keep it as-is.
- Run `./run check:all` to verify no regressions.
