# Phase 1: Create New Attribute Classes

## Objective

Create 6 PHP attribute classes that fill the convention-based gaps in the current metadata system. After this phase, every structural relationship in the project can be expressed via a PHP attribute. No code uses these attributes yet — that happens in Phase 2.

## Background

The inventory system currently derives these relationships by parsing Blade templates with regex:

| Relationship | Current source | Regex in `inventory.php` |
|---|---|---|
| View → Layout | `@extends('base')` in template | `/@extends\([\'"]([^\'"]+)[\'"]\)/` |
| View → Component | `<x-button>` in template | `/<x-([\w-]+)/` |
| View → ViewModel | `use ...ViewModels\X;` in template | `/use\s+\S+\\\\ViewModels\\\\(\w+)\s*;/` |
| View title | `@section('title', '...')` in template | Not currently extracted |
| Component → Props | `@props([...])` in template | `/@props\(\[(.*?)\]\)/s` |
| Test → Route coverage | `Route::caseName` in test body | `/\bRoute::(\w+)\b/` |

Each new attribute replaces one of these regex extractions.

## Deliverables

### 1. `src/Attributes/ExtendsLayout.php`

```php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares which layout template a view extends.
 *
 * Applied to View enum cases. Replaces @extends() as the structural metadata source.
 *
 * @example
 * #[ExtendsLayout('base')]
 * case home = 'home';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class ExtendsLayout
{
    public function __construct(
        public string $layout,
    ) {}
}
```

- **Target:** `CLASS_CONSTANT` (View enum cases)
- **Repeatable:** No (a view extends exactly one layout)
- **Parameter:** `string $layout` — layout template name (e.g., `'base'`)

### 2. `src/Attributes/UsesComponent.php`

```php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Blade\Component;

/**
 * Declares which Blade components a view uses.
 *
 * Applied to View enum cases. Replaces <x-*> tag scanning as the structural metadata source.
 *
 * @example
 * #[UsesComponent(Component::card, Component::button)]
 * case home = 'home';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class UsesComponent
{
    /** @var Component[] */
    public array $components;

    public function __construct(Component ...$components)
    {
        $this->components = $components;
    }
}
```

- **Target:** `CLASS_CONSTANT` (View enum cases)
- **Repeatable:** No (variadic covers multiple components in one attribute)
- **Parameter:** `Component ...$components` — variadic list of Component enum cases

### 3. `src/Attributes/ReceivesViewModel.php`

```php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares which ViewModels a view receives as template data.
 *
 * Applied to View enum cases. Replaces `use` import scanning as the structural metadata source.
 *
 * @example
 * #[ReceivesViewModel(ErrorViewModel::class)]
 * case error = 'error';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class ReceivesViewModel
{
    /** @var class-string[] */
    public array $viewModels;

    /** @param class-string ...$viewModels */
    public function __construct(string ...$viewModels)
    {
        $this->viewModels = $viewModels;
    }
}
```

- **Target:** `CLASS_CONSTANT` (View enum cases)
- **Repeatable:** No (variadic covers multiple viewmodels)
- **Parameter:** `string ...$viewModels` — variadic list of ViewModel FQCNs

### 4. `src/Attributes/PageTitle.php`

```php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the HTML page title for a view.
 *
 * Applied to View enum cases. Replaces @section('title', '...') as the structural metadata source.
 *
 * @example
 * #[PageTitle('Register — Thryds')]
 * case register = 'register';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class PageTitle
{
    public function __construct(
        public string $title,
    ) {}
}
```

- **Target:** `CLASS_CONSTANT` (View enum cases)
- **Repeatable:** No
- **Parameter:** `string $title` — full page title string

### 5. `src/Attributes/Prop.php`

```php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares a prop accepted by a Blade component.
 *
 * Applied to Component enum cases. Replaces @props() parsing as the structural metadata source.
 *
 * @example
 * #[Prop('variant', default: 'primary', enum: ButtonVariant::class)]
 * #[Prop('size', default: 'md', enum: ButtonSize::class)]
 * #[Prop('type', default: 'button')]
 * case button = 'button';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
readonly class Prop
{
    /**
     * @param string $name Prop name as used in the template
     * @param string $default Default value (the resolved string, not the enum expression)
     * @param class-string|null $enum Backing enum class if the prop is enum-constrained, null otherwise
     */
    public function __construct(
        public string $name,
        public string $default = '',
        public ?string $enum = null,
    ) {}
}
```

- **Target:** `CLASS_CONSTANT` (Component enum cases)
- **Repeatable:** Yes (one per prop)
- **Parameters:** `string $name`, `string $default`, `?string $enum`

### 6. `src/Attributes/CoversRoute.php`

```php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Routes\Route;

/**
 * Declares which routes a test class covers.
 *
 * Applied to test classes. Replaces Route:: reference scanning as the structural metadata source.
 *
 * @example
 * #[CoversRoute(Route::home)]
 * final class HomeControllerTest extends IntegrationTestCase
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class CoversRoute
{
    /** @var Route[] */
    public array $routes;

    public function __construct(Route ...$routes)
    {
        $this->routes = $routes;
    }
}
```

- **Target:** `CLASS` (test classes)
- **Repeatable:** No (variadic covers multiple routes)
- **Parameter:** `Route ...$routes` — variadic list of Route enum cases

## File Checklist

| File | Action | Status |
|---|---|---|
| `src/Attributes/ExtendsLayout.php` | Create | |
| `src/Attributes/UsesComponent.php` | Create | |
| `src/Attributes/ReceivesViewModel.php` | Create | |
| `src/Attributes/PageTitle.php` | Create | |
| `src/Attributes/Prop.php` | Create | |
| `src/Attributes/CoversRoute.php` | Create | |

## Verification

```bash
# All new files must be loadable
./run check:types        # PHPStan passes — no type errors in new attributes
./run check:style        # Code style passes
./run check:all          # Full suite green — no behavioral changes
```

## Notes

- These attributes are created but not applied yet. Applying them to existing code is Phase 2.
- The attribute constructors use typed enums (`Component`, `Route`) to enforce valid references at compile time. An invalid `#[UsesComponent(Component::nonexistent)]` is a PHP error, not a runtime bug.
- `Prop` is the only repeatable attribute (one instance per prop). The others use variadic parameters to keep the annotation surface compact.
