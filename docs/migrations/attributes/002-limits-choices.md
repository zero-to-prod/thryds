# Attribute Migration: #[LimitsChoices]

**ADR-007 leg:** Enums limit choices

## Problem

Five Rector rules reference backed enum classes via hardcoded config:

```php
// rector.php — current state (duplicated enum lists)
$rectorConfig->ruleWithConfiguration(RequireEnumValueAccessRector::class, [
    'enumClasses' => [View::class, Route::class, HTTP_METHOD::class, AppEnv::class, LogLevel::class],
]);
$rectorConfig->ruleWithConfiguration(ForbidStringComparisonOnEnumPropertyRector::class, [
    'enumClasses' => [AppEnv::class, Route::class, HTTP_METHOD::class, LogLevel::class, View::class],
]);
$rectorConfig->ruleWithConfiguration(ForbidHardcodedRouteStringRector::class, [
    'enumClass' => Route::class,
]);
$rectorConfig->ruleWithConfiguration(RequireAllRouteCasesRegisteredRector::class, [
    'enumClass' => Route::class,
]);
$rectorConfig->ruleWithConfiguration(RequireRouteTestRector::class, [
    'enumClass' => Route::class,
]);
```

Adding a new enum (e.g., `Permission`, `NotificationType`) requires updating every relevant rule. The enum lists are duplicated and can drift. An AI agent must scan rector.php to understand which enums are "important."

## Attribute definition

```php
// src/Helpers/LimitsChoices.php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Marks a backed enum as a domain constraint — a finite set of valid values.
 *
 * Rector rules discover these enums via reflection to auto-configure themselves.
 * AI agents read the domain and used_in parameters to understand what the enum constrains
 * and where new cases must be handled.
 *
 * @example #[LimitsChoices(domain: 'URL routes', used_in: ['Router::map() arg 2', 'templates'])]
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class LimitsChoices
{
    /**
     * @param string   $domain     Human-readable name of the domain this enum constrains.
     * @param string[] $usedIn     Where this enum's values are consumed. Each entry is a human-readable
     *                             location (method signature, file pattern, etc.) that an agent can search for.
     * @param bool     $requireRegistration  If true, Rector verifies all cases are registered in a map()-style call.
     * @param bool     $requireTests         If true, Rector verifies all cases have test coverage.
     */
    public function __construct(
        public string $domain,
        public array $usedIn = [],
        public bool $requireRegistration = false,
        public bool $requireTests = false,
    ) {}
}
```

## Files to apply the attribute

### `src/Routes/Route.php`

```php
// Before
enum Route: string
{
    case home = '/';
    case about = '/about';
    case opcache_status = '/_opcache/status';
    case opcache_scripts = '/_opcache/scripts';
    // ...
}

// After
use ZeroToProd\Thryds\Helpers\ClosedSet;

#[ClosedSet(
    domain: 'URL routes',
    used_in: ['Router::map() arg 2', 'templates/*.blade.php href attributes'],
    requireRegistration: true,
    requireTests: true,
)]
enum Route: string
{
    case home = '/';
    case about = '/about';
    case opcache_status = '/_opcache/status';
    case opcache_scripts = '/_opcache/scripts';
    // ...
}
```

### `src/Helpers/View.php`

```php
// Before
enum View: string
{
    case about = 'about';
    case error = 'error';
    case home = 'home';
}

// After
use ZeroToProd\Thryds\Helpers\ClosedSet;

#[ClosedSet(
    domain: 'Blade templates',
    used_in: ['Blade::make(view:)'],
)]
enum View: string
{
    case about = 'about';
    case error = 'error';
    case home = 'home';
}
```

### `src/AppEnv.php`

```php
// Before
enum AppEnv: string
{
    case production = 'production';
    case development = 'development';
}

// After
use ZeroToProd\Thryds\Helpers\ClosedSet;

#[ClosedSet(
    domain: 'application environment',
    used_in: ['Config::$AppEnv', 'Blade @production / @env directives'],
)]
enum AppEnv: string
{
    case production = 'production';
    case development = 'development';
}
```

### `src/Routes/HTTP_METHOD.php`

```php
// Before
enum HTTP_METHOD: string
{
    case GET = 'GET';
    case POST = 'POST';
    // ...
}

// After
use ZeroToProd\Thryds\Helpers\ClosedSet;

#[ClosedSet(
    domain: 'HTTP methods',
    used_in: ['Router::map() arg 1'],
)]
enum HTTP_METHOD: string
{
    case GET = 'GET';
    case POST = 'POST';
    // ...
}
```

### `src/LogLevel.php`

```php
// Before
enum LogLevel: int
{
    case Debug = FRANKENPHP_LOG_LEVEL_DEBUG;
    // ...
}

// After
use ZeroToProd\Thryds\Helpers\ClosedSet;

#[ClosedSet(
    domain: 'log severity levels',
    used_in: ['Log::debug/info/warn/error → frankenphp_log()'],
)]
enum LogLevel: int
{
    case Debug = FRANKENPHP_LOG_LEVEL_DEBUG;
    // ...
}
```

## Rector rule changes

### `RequireEnumValueAccessRector` — auto-discover via attribute

```php
// Before (rector.php)
$rectorConfig->ruleWithConfiguration(RequireEnumValueAccessRector::class, [
    'enumClasses' => [View::class, Route::class, HTTP_METHOD::class, AppEnv::class, LogLevel::class],
    'mode' => 'auto',
]);

// After — discovers all #[LimitsChoices] enums automatically
$rectorConfig->ruleWithConfiguration(RequireEnumValueAccessRector::class, [
    'mode' => 'auto',
]);
```

**Rule implementation change:**

```php
// In RequireEnumValueAccessRector::discoverFromAttribute()
if ($this->enumClasses === []) {
    foreach ($this->reflectionProvider->getClassesInPaths($this->paths) as $classReflection) {
        if (!$classReflection->isEnum()) continue;
        $attrs = $classReflection->getNativeReflection()->getAttributes(LimitsChoices::class);
        if ($attrs !== []) {
            $this->enumClasses[] = $classReflection->getName();
        }
    }
}
```

### `ForbidStringComparisonOnEnumPropertyRector` — same pattern

```php
// Before
'enumClasses' => [AppEnv::class, Route::class, HTTP_METHOD::class, LogLevel::class, View::class],

// After — no enumClasses needed, discovers from #[LimitsChoices]
```

### `RequireAllRouteCasesRegisteredRector` — discover from attribute flag

```php
// Before
'enumClass' => Route::class,

// After — finds enums where #[LimitsChoices(requireRegistration: true)]
```

**Rule implementation change:** Scan for `#[LimitsChoices]` where `$instance->requireRegistration === true`. If exactly one match, use it. If multiple, require explicit config.

### `RequireRouteTestRector` — discover from attribute flag

```php
// Before
'enumClass' => Route::class,

// After — finds enums where #[LimitsChoices(requireTests: true)]
```

### `ForbidHardcodedRouteStringRector` — discover from attribute

All `#[LimitsChoices]` enums with string backing types become candidates. The rule builds value→case maps from each, then checks for matching strings in source.

## New Rector rule: `RequireEnumCaseForNewValueRector`

When a `#[LimitsChoices]` enum's domain is used (detected from `used_in` patterns), and a bare string is passed that doesn't match any existing case, suggest adding a new enum case.

```php
// Detects:
$Router->map('GET', '/new-page', $handler);
// Where '/new-page' doesn't match any Route case
// → TODO: Add a Route enum case for '/new-page' or use an existing case.
```

## AI agent impact

An agent asked "I need to add a new page" can:

```
1. Grep for #[LimitsChoices(domain: 'URL routes')] → finds Route.php
2. Read `requireRegistration: true` → knows it must register the route in Router::map()
3. Read `requireTests: true` → knows it must add a test
4. Read `used_in` → knows Route is used in Router::map() arg 2 and templates
5. Grep for #[LimitsChoices(domain: 'Blade templates')] → finds View.php
6. Read `used_in` → knows to use View::case->value in Blade::make(view:)
```

All from attribute metadata — no rector.php parsing, no docblock interpretation.

## Files to create/modify

| File | Action |
|------|--------|
| `src/Helpers/LimitsChoices.php` | Create attribute class |
| `src/Routes/Route.php` | Add `#[LimitsChoices]` |
| `src/Helpers/View.php` | Add `#[LimitsChoices]` |
| `src/AppEnv.php` | Add `#[LimitsChoices]` |
| `src/Routes/HTTP_METHOD.php` | Add `#[LimitsChoices]` |
| `src/LogLevel.php` | Add `#[LimitsChoices]` |
| `rector.php` | Remove hardcoded `enumClasses` / `enumClass` from 5 rules |
| `utils/rector/src/RequireEnumValueAccessRector.php` | Add attribute discovery fallback |
| `utils/rector/src/ForbidStringComparisonOnEnumPropertyRector.php` | Add attribute discovery fallback |
| `utils/rector/src/RequireAllRouteCasesRegisteredRector.php` | Add attribute discovery fallback |
| `utils/rector/src/RequireRouteTestRector.php` | Add attribute discovery fallback |
| `utils/rector/src/ForbidHardcodedRouteStringRector.php` | Add attribute discovery fallback |

## Migration order

1. Create `src/Helpers/LimitsChoices.php`.
2. Add `#[LimitsChoices]` to all 5 enums.
3. Run `./run check:all` to verify no regressions.
4. Update Rector rules to support attribute discovery (with fallback to explicit config).
5. Simplify `rector.php` configs.
6. Run `./run check:all` + `./run test:rector` to verify.
