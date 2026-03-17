# Attribute Migration: #[NamesKeys]

**ADR-007 leg:** Constants name things

## Problem

Rector rules that enforce constant usage must be configured with hardcoded class references:

```php
// rector.php — current state
$rectorConfig->ruleWithConfiguration(ForbidBareServerEnvKeyRector::class, [
    'envClass' => \ZeroToProd\Thryds\Env::class,          // hardcoded
]);
$rectorConfig->ruleWithConfiguration(UseLogContextConstRector::class, [
    'logClass' => Log::class,                               // hardcoded
]);
$rectorConfig->ruleWithConfiguration(ForbidMagicStringArrayKeyRector::class, [
    'excludedClasses' => [Log::class],                      // hardcoded
]);
```

Adding a new constants class (e.g., `CacheKey`, `QueueName`) requires updating every relevant Rector rule config. Nothing enforces that the class's constants follow a consistent pattern. An AI agent must read docblocks or filenames to understand that `Env` names `$_SERVER` keys while `Header` names HTTP headers.

## Attribute definition

```php
// src/Helpers/NamesKeys.php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Marks a class whose public string constants name keys in a specific context.
 *
 * Rector rules discover these classes via reflection to auto-configure themselves.
 * AI agents read the source and access parameters to understand how the class is used.
 *
 * @example #[NamesKeys(source: '$_SERVER')]
 * @example #[NamesKeys(source: 'opcache_get_status()')]
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class NamesKeys
{
    /**
     * @param string   $source  Human-readable name of the data source whose keys these constants name.
     *                          Convention: use the PHP expression that produces the data (e.g., '$_SERVER', 'opcache_get_status()').
     * @param string   $access  Example usage pattern showing how to access a key using the constant.
     *                          Helps AI agents generate correct code without reading all call sites.
     * @param string[] $superglobals  If the source is a superglobal, list the variable names (e.g., ['_SERVER', '_ENV']).
     *                                Rector rules use this to match ArrayDimFetch nodes on these variables.
     */
    public function __construct(
        public string $source,
        public string $access = '',
        public array $superglobals = [],
    ) {}
}
```

## Files to apply the attribute

### `src/Env.php`

```php
// Before
readonly class Env
{
    public const string APP_ENV = 'APP_ENV';
    public const string MAX_REQUESTS = 'MAX_REQUESTS';
    public const string FRANKENPHP_HOT_RELOAD = 'FRANKENPHP_HOT_RELOAD';
}

// After
use ZeroToProd\Thryds\Helpers\KeyRegistry;

#[KeyRegistry(
    source: '$_SERVER / $_ENV',
    used_in: '$_SERVER[Env::KEY] ?? $_ENV[Env::KEY]',
    superglobals: ['_SERVER', '_ENV'],
)]
readonly class Env
{
    public const string APP_ENV = 'APP_ENV';
    public const string MAX_REQUESTS = 'MAX_REQUESTS';
    public const string FRANKENPHP_HOT_RELOAD = 'FRANKENPHP_HOT_RELOAD';
}
```

### `src/Header.php`

```php
// Before
/** HTTP header name constants. */
readonly class Header
{
    public const string request_id = 'X-Request-ID';
}

// After
use ZeroToProd\Thryds\Helpers\KeyRegistry;

#[KeyRegistry(
    source: 'HTTP headers',
    used_in: '$request->getHeaderLine(Header::KEY)',
)]
readonly class Header
{
    public const string request_id = 'X-Request-ID';
}
```

### `src/OpcacheStatus.php`

```php
// Before
/** @see opcache_get_status() */
readonly class OpcacheStatus
{
    public const string scripts = 'scripts';
    // ...
}

// After
use ZeroToProd\Thryds\Helpers\KeyRegistry;

#[KeyRegistry(
    source: 'opcache_get_status()',
    used_in: '$status[OpcacheStatus::KEY]',
)]
readonly class OpcacheStatus
{
    public const string scripts = 'scripts';
    // ...
}
```

### `src/Log.php` (constants portion only)

```php
// Before
readonly class Log
{
    public const string event = 'event';
    // ...
    public static function error(...): void { ... }
}

// After
use ZeroToProd\Thryds\Helpers\KeyRegistry;

#[KeyRegistry(
    source: 'Log context array',
    used_in: 'Log::error($msg, [Log::KEY => ...])',
)]
readonly class Log
{
    public const string event = 'event';
    // ...
    public static function error(...): void { ... }
}
```

## Rector rule changes

### `ForbidBareServerEnvKeyRector` — auto-discover via attribute

```php
// Before (rector.php)
$rectorConfig->ruleWithConfiguration(ForbidBareServerEnvKeyRector::class, [
    'envClass' => \ZeroToProd\Thryds\Env::class,
    'superglobals' => ['_SERVER', '_ENV'],
    'mode' => 'auto',
]);

// After (rector.php) — no envClass or superglobals needed
$rectorConfig->ruleWithConfiguration(ForbidBareServerEnvKeyRector::class, [
    'mode' => 'auto',
]);
```

**Rule implementation change:** In `configure()`, if `envClass` is not provided, scan all classes in `$rectorConfig->paths()` for the `#[NamesKeys]` attribute where `superglobals` is non-empty. Use the first match. The attribute's `superglobals` array replaces the `superglobals` config key.

```php
// In ForbidBareServerEnvKeyRector::discoverFromAttribute()
$classes = $this->reflectionProvider->getClassesInPaths($this->paths);
foreach ($classes as $classReflection) {
    $attrs = $classReflection->getNativeReflection()->getAttributes(NamesKeys::class);
    if ($attrs !== []) {
        $instance = $attrs[0]->newInstance();
        if ($instance->superglobals !== []) {
            $this->envClass = $classReflection->getName();
            $this->superglobals = $instance->superglobals;
            break;
        }
    }
}
```

### `UseLogContextConstRector` — auto-discover via attribute

```php
// Before (rector.php)
$rectorConfig->ruleWithConfiguration(UseLogContextConstRector::class, [
    'logClass' => Log::class,
    'keys' => ['exception', 'file', 'line'],
    'mode' => 'auto',
]);

// After — logClass discovered from #[NamesKeys(source: 'Log context array')]
// keys can be derived from the class's public const string declarations
$rectorConfig->ruleWithConfiguration(UseLogContextConstRector::class, [
    'mode' => 'auto',
]);
```

### `ForbidMagicStringArrayKeyRector` — auto-discover exclusions

```php
// Before (rector.php)
$rectorConfig->ruleWithConfiguration(ForbidMagicStringArrayKeyRector::class, [
    'excludedClasses' => [Log::class],
]);

// After — exclude all #[NamesKeys] classes automatically
$rectorConfig->ruleWithConfiguration(ForbidMagicStringArrayKeyRector::class, [
    'mode' => 'warn',
]);
```

**Rule implementation change:** In `collectExcludedItems()`, auto-exclude static calls on any class that has the `#[NamesKeys]` attribute — those classes own their own key enforcement.

## New Rector rule: `RequireNamesKeysConstantPatternRector`

Validates that every `public const string` in a `#[NamesKeys]` class follows the pattern where the constant value matches the constant name (for non-header classes) or is a valid key string.

```php
// Catches:
#[NamesKeys(source: '$_SERVER')]
readonly class Env
{
    public const string app_env = 'APP_ENV'; // WARN: name 'app_env' doesn't match value 'APP_ENV'
}
```

## AI agent impact

An agent asked "where does the app read environment variables from?" can:

```
1. Grep for #[NamesKeys(source: '$_SERVER')] → finds Env.php
2. Read the `access` param → knows the pattern is $_SERVER[Env::KEY]
3. Read all public const string → knows the complete list of keys
4. Read `superglobals` → knows to check both $_SERVER and $_ENV
```

No docblock parsing, no codebase exploration, no git blame needed.

## Files to create/modify

| File | Action |
|------|--------|
| `src/Helpers/NamesKeys.php` | Create attribute class |
| `src/Env.php` | Add `#[NamesKeys]` |
| `src/Header.php` | Add `#[NamesKeys]` |
| `src/OpcacheStatus.php` | Add `#[NamesKeys]` |
| `src/Log.php` | Add `#[NamesKeys]` |
| `rector.php` | Simplify `ForbidBareServerEnvKeyRector`, `UseLogContextConstRector`, `ForbidMagicStringArrayKeyRector` configs |
| `utils/rector/src/ForbidBareServerEnvKeyRector.php` | Add attribute discovery fallback |
| `utils/rector/src/UseLogContextConstRector.php` | Add attribute discovery fallback |
| `utils/rector/src/ForbidMagicStringArrayKeyRector.php` | Auto-exclude `#[NamesKeys]` classes |

## Migration order

1. Create `src/Helpers/NamesKeys.php`.
2. Add `#[NamesKeys]` to `Env`, `Header`, `OpcacheStatus`, `Log`.
3. Run `./run check:all` to verify no regressions.
4. Update Rector rules to support attribute discovery (with fallback to explicit config).
5. Simplify `rector.php` configs.
6. Run `./run check:all` + `./run test:rector` to verify.
