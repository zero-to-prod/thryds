# Attribute Migration: #[SourceOfTruth]

**ADR-007 leg:** Cross-cutting — reinforces all three legs

## Problem

Domain concept classes (`Route`, `View`, `Env`, `DevFilter`) are the canonical owners of specific data, but nothing declares that ownership or lists where the data is consumed. Today:

- `Route` is referenced in `WebRoutes.php`, `IntegrationTestCase.php`, templates, `opcache-audit.php`, `generate-preload.php`, `lint-blade-routes.php` — but you only discover this by grepping.
- `DevFilter` is used by `generate-preload.php` and `opcache-audit.php` — documented in a docblock, but not machine-readable.
- When a new `Route` case is added, the developer must know to update `WebRoutes`, add a test, add a template, and update the preload script's template rendering list. This knowledge is tribal.

## Attribute definition

```php
// src/Helpers/SourceOfTruth.php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Declares a class or enum as the canonical owner of a domain concept.
 *
 * Lists the files/classes that consume this data. Rector rules verify that
 * consumers actually reference the source class. AI agents use the consumers
 * list as a dependency map when making changes.
 *
 * @example #[SourceOfTruth(for: 'route paths', consumers: [WebRoutes::class, 'templates/*.blade.php'])]
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class SourceOfTruth
{
    /**
     * @param string   $for        Human-readable name of the concept this class owns.
     * @param string[] $consumers  Where this class's data is consumed. Each entry is either:
     *                             - A class FQN (verified by Rector: must import the source class)
     *                             - A file glob pattern (verified by Rector: at least one match must reference the source)
     * @param string   $addCase    Human-readable checklist for what to do when adding a new case/constant.
     *                             AI agents read this to know the full set of changes required.
     */
    public function __construct(
        public string $for,
        public array $consumers = [],
        public string $addCase = '',
    ) {}
}
```

## Files to apply the attribute

### `src/Routes/Route.php`

```php
// Before
#[LimitsChoices(domain: 'URL routes', usedIn: [...], requireRegistration: true, requireTests: true)]
enum Route: string { ... }

// After (stacks with #[LimitsChoices])
use ZeroToProd\Thryds\Helpers\SourceOfTruth;

#[SourceOfTruth(
    for: 'route paths',
    consumers: [
        \ZeroToProd\Thryds\Routes\WebRoutes::class,
        \ZeroToProd\Thryds\Tests\Integration\IntegrationTestCase::class,
        'templates/*.blade.php',
        'scripts/opcache-audit.php',
        'scripts/generate-preload.php',
    ],
    addCase: '1. Add enum case. 2. Register in WebRoutes::register(). 3. Create controller + template. 4. Add integration test. 5. Add template render in generate-preload.php.',
)]
#[LimitsChoices(domain: 'URL routes', usedIn: ['Router::map() arg 2'], requireRegistration: true, requireTests: true)]
enum Route: string { ... }
```

### `src/Helpers/View.php`

```php
// Before
#[LimitsChoices(domain: 'Blade templates', usedIn: ['Blade::make(view:)'])]
enum View: string { ... }

// After
use ZeroToProd\Thryds\Helpers\SourceOfTruth;

#[SourceOfTruth(
    for: 'Blade template names',
    consumers: [
        \ZeroToProd\Thryds\Controllers\HomeController::class,
        \ZeroToProd\Thryds\Routes\WebRoutes::class,
        'public/index.php',
        'scripts/generate-preload.php',
        'scripts/production-checklist.php',
        \ZeroToProd\Thryds\Tests\Integration\BladeCacheTest::class,
    ],
    addCase: '1. Add enum case. 2. Create templates/{case}.blade.php. 3. Add render call in generate-preload.php. 4. Add to production-checklist.php view_data.',
)]
#[LimitsChoices(domain: 'Blade templates', usedIn: ['Blade::make(view:)'])]
enum View: string { ... }
```

### `src/Env.php`

```php
// Before
#[NamesKeys(source: '$_SERVER / $_ENV', access: '...', superglobals: ['_SERVER', '_ENV'])]
readonly class Env { ... }

// After
use ZeroToProd\Thryds\Helpers\SourceOfTruth;

#[SourceOfTruth(
    for: 'environment variable keys',
    consumers: [
        \ZeroToProd\Thryds\App::class,
        'public/index.php',
    ],
    addCase: '1. Add constant. 2. Add to compose.yaml environment section if needed. 3. Add to .env.example.',
)]
#[NamesKeys(source: '$_SERVER / $_ENV', access: '$_SERVER[Env::KEY] ?? $_ENV[Env::KEY]', superglobals: ['_SERVER', '_ENV'])]
readonly class Env { ... }
```

### `src/DevFilter.php`

```php
// Before
readonly class DevFilter { ... }

// After
use ZeroToProd\Thryds\Helpers\SourceOfTruth;

#[SourceOfTruth(
    for: 'dev-only path filters',
    consumers: [
        'scripts/generate-preload.php',
        'scripts/opcache-audit.php',
    ],
    addCase: '1. Add path to dev_vendors or excluded_dirs. Both consumers use DevFilter::isDevPath() so no further changes needed.',
)]
readonly class DevFilter { ... }
```

## New Rector rule: `ValidateSourceOfTruthConsumersRector`

Verifies that every entry in `consumers` actually references the source class.

### Behavior

For each class with `#[SourceOfTruth]`:

1. **Class consumers** (FQN entries): Verify the consumer file contains a `use` import of the source class. If not → warn that the consumer list is stale.
2. **Glob consumers** (path patterns): Verify at least one matching file contains a reference to the source class (by short name or FQN). If not → warn.

### Configuration

```php
$rectorConfig->ruleWithConfiguration(ValidateSourceOfTruthConsumersRector::class, [
    'mode' => 'warn',
    'message' => "TODO: [ValidateSourceOfTruthConsumersRector] %s declares %s as a consumer, but it does not reference %s. Update the consumers list.",
    'projectDir' => __DIR__,
]);
```

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `mode` | `'warn'` | `'warn'` | Warn only — consumer lists may include indirect references |
| `message` | `string` | See above | `sprintf`: `%1$s` = source class, `%2$s` = consumer entry, `%3$s` = source short name |
| `projectDir` | `string` | (required) | Root directory for resolving glob patterns |

### Implementation

```php
// Node type: Class_ (or Enum_)
public function refactor(Node $node): ?Node
{
    $className = $this->getName($node);
    if ($className === null) return null;

    $reflection = $this->reflectionProvider->getClass($className);
    $attrs = $reflection->getNativeReflection()->getAttributes(SourceOfTruth::class);
    if ($attrs === []) return null;

    $instance = $attrs[0]->newInstance();
    $violations = [];

    foreach ($instance->consumers as $consumer) {
        if (class_exists($consumer) || interface_exists($consumer)) {
            // Verify the consumer file imports this class
            $consumerReflection = $this->reflectionProvider->getClass($consumer);
            $consumerFile = $consumerReflection->getFileName();
            if ($consumerFile && !$this->fileReferences($consumerFile, $className)) {
                $violations[] = $consumer;
            }
        } elseif (str_contains($consumer, '*') || str_contains($consumer, '/')) {
            // Glob pattern — verify at least one match references this class
            $files = glob($this->projectDir . '/' . $consumer);
            $shortName = $this->shortName($className);
            $found = false;
            foreach ($files as $file) {
                if (str_contains(file_get_contents($file), $shortName)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $violations[] = $consumer;
            }
        }
    }

    if ($violations !== []) {
        // Add TODO comment
    }

    return null;
}

private function fileReferences(string $filePath, string $className): bool
{
    $content = file_get_contents($filePath);
    $shortName = $this->shortName($className);
    return str_contains($content, $className) || str_contains($content, $shortName);
}
```

### Test structure

```
utils/rector/tests/ValidateSourceOfTruthConsumersRector/
├── ValidateSourceOfTruthConsumersRectorTest.php
├── config/
│   └── configured_rule.php
├── Support/
│   ├── SourceClass.php         # Class with #[SourceOfTruth]
│   ├── ValidConsumer.php       # Imports SourceClass
│   └── StaleConsumer.php       # Does NOT import SourceClass
└── Fixture/
    ├── flags_stale_class_consumer.php.inc
    └── skips_valid_class_consumer.php.inc
```

### Fixture: `flags_stale_class_consumer.php.inc`

```php
<?php

use Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector\SourceOfTruth;

#[SourceOfTruth(
    for: 'test data',
    consumers: ['Utils\\Rector\\Tests\\ValidateSourceOfTruthConsumersRector\\StaleConsumer'],
)]
class TestSource
{
    public const string key = 'key';
}

?>
-----
<?php

use Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector\SourceOfTruth;

// TODO: [ValidateSourceOfTruthConsumersRector] TestSource declares StaleConsumer as a consumer, but it does not reference TestSource. Update the consumers list.
#[SourceOfTruth(
    for: 'test data',
    consumers: ['Utils\\Rector\\Tests\\ValidateSourceOfTruthConsumersRector\\StaleConsumer'],
)]
class TestSource
{
    public const string key = 'key';
}

?>
```

## AI agent impact

An agent asked "add a new route `/dashboard`" can:

```
1. Grep for #[SourceOfTruth(for: 'route paths')] → finds Route.php
2. Read addCase → gets the complete checklist:
   "1. Add enum case.
    2. Register in WebRoutes::register().
    3. Create controller + template.
    4. Add integration test.
    5. Add template render in generate-preload.php."
3. Read consumers → knows exactly which files to update
4. Execute each step
5. Run ./run check:all — Rector verifies registration + test coverage
```

Without the attribute, the agent must:
- Grep for all Route references
- Guess which files need updating
- Miss generate-preload.php (not obvious)
- Miss production-checklist.php (not obvious)

The `addCase` field is the "agent instruction manual" for extending the domain concept.

## Interaction with other attributes

`#[SourceOfTruth]` stacks with `#[LimitsChoices]` and `#[NamesKeys]`:

```
#[SourceOfTruth]    → WHO consumes this, WHAT to do when adding cases
#[LimitsChoices]    → WHY this enum exists, WHERE values are used, WHAT Rector enforces
#[NamesKeys]        → WHAT data source the keys come from, HOW to access them
```

Each answers a different question. Together they give an AI agent (or developer) complete context without leaving the source file.

## Files to create/modify

| File | Action |
|------|--------|
| `src/Helpers/SourceOfTruth.php` | Create attribute class |
| `src/Routes/Route.php` | Add `#[SourceOfTruth]` |
| `src/Helpers/View.php` | Add `#[SourceOfTruth]` |
| `src/Env.php` | Add `#[SourceOfTruth]` |
| `src/DevFilter.php` | Add `#[SourceOfTruth]` |
| `utils/rector/src/ValidateSourceOfTruthConsumersRector.php` | Create new rule |
| `utils/rector/tests/ValidateSourceOfTruthConsumersRector/` | Create test directory |
| `rector.php` | Register `ValidateSourceOfTruthConsumersRector` |

## Migration order

1. Create `src/Helpers/SourceOfTruth.php`.
2. Add `#[SourceOfTruth]` to `Route`, `View`, `Env`, `DevFilter`.
3. Run `./run check:all` to verify no regressions.
4. Create `ValidateSourceOfTruthConsumersRector` with tests.
5. Register in `rector.php`.
6. Run `./run check:all` + `./run test:rector` to verify.
