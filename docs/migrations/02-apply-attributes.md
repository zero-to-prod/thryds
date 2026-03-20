# Phase 2: Apply Attributes to Existing Code

## Objective

Apply the 6 new attributes from Phase 1 to every View enum case, Component enum case, and integration test class. After this phase, every structural relationship in the codebase is expressed via PHP attributes. The convention-based regex parsing in inventory still runs (removal is Phase 3), but the attributes now exist alongside it.

## Prerequisites

- Phase 1 complete: all 6 attribute classes exist in `src/Attributes/`

## Deliverables

### 1. `src/Blade/View.php` — Apply to every enum case

Current state:
```php
enum View: string
{
    case about = 'about';
    case error = 'error';
    case home = 'home';
    case login = 'login';
    case register = 'register';
    case styleguide = 'styleguide';
}
```

Target state (attribute values derived from reading each template):

```php
use ZeroToProd\Thryds\Attributes\ExtendsLayout;
use ZeroToProd\Thryds\Attributes\PageTitle;
use ZeroToProd\Thryds\Attributes\ReceivesViewModel;
use ZeroToProd\Thryds\Attributes\UsesComponent;
use ZeroToProd\Thryds\Blade\Component;
use ZeroToProd\Thryds\ViewModels\ErrorViewModel;

enum View: string
{
    #[ExtendsLayout('base')]
    #[PageTitle('About — Thryds')]
    #[UsesComponent(Component::card)]
    case about = 'about';

    #[ExtendsLayout('base')]
    #[PageTitle('Error — Thryds')]
    #[UsesComponent(Component::alert, Component::card)]
    #[ReceivesViewModel(ErrorViewModel::class)]
    case error = 'error';

    #[ExtendsLayout('base')]
    #[PageTitle('Thryds')]
    #[UsesComponent(Component::card, Component::button)]
    case home = 'home';

    #[ExtendsLayout('base')]
    #[PageTitle('Login — Thryds')]
    #[UsesComponent(Component::card, Component::form_group, Component::input, Component::button)]
    case login = 'login';

    #[ExtendsLayout('base')]
    #[PageTitle('Register — Thryds')]
    #[UsesComponent(Component::card, Component::form_group, Component::input, Component::button)]
    case register = 'register';

    #[ExtendsLayout('base')]
    #[PageTitle('Styleguide — Thryds')]
    #[UsesComponent(Component::alert, Component::button, Component::card, Component::form_group, Component::input)]
    case styleguide = 'styleguide';
}
```

**How to derive these values** — read each `templates/*.blade.php` file:
- `ExtendsLayout`: from `@extends('...')` directive
- `PageTitle`: from `@section('title', '...')` directive
- `UsesComponent`: from all `<x-*>` tags in the template (deduplicated, sorted)
- `ReceivesViewModel`: from `use ...\ViewModels\*` imports in `@php` blocks

### 2. `src/Blade/Component.php` — Apply `#[Prop]` to every enum case

Current state:
```php
enum Component: string
{
    /** Inline status banner for feedback messages (info, danger, success). */
    case alert = 'alert';
    /** Action trigger with configurable visual variant and size. */
    case button = 'button';
    /** Contained surface for grouping related content. */
    case card = 'card';
    /** Label + input wrapper that enforces consistent form field layout. */
    case form_group = 'form-group';
    /** Text field bound to a typed HTML input type. */
    case input = 'input';
}
```

Target state (prop values derived from reading each component template's `@props`):

```php
use ZeroToProd\Thryds\Attributes\Prop;
use ZeroToProd\Thryds\UI\AlertVariant;
use ZeroToProd\Thryds\UI\ButtonSize;
use ZeroToProd\Thryds\UI\ButtonVariant;
use ZeroToProd\Thryds\UI\InputType;

enum Component: string
{
    /** Inline status banner for feedback messages (info, danger, success). */
    #[Prop('variant', default: 'info', enum: AlertVariant::class)]
    case alert = 'alert';

    /** Action trigger with configurable visual variant and size. */
    #[Prop('variant', default: 'primary', enum: ButtonVariant::class)]
    #[Prop('size', default: 'md', enum: ButtonSize::class)]
    #[Prop('type', default: 'button')]
    case button = 'button';

    /** Contained surface for grouping related content. */
    case card = 'card';

    /** Label + input wrapper that enforces consistent form field layout. */
    #[Prop('label')]
    case form_group = 'form-group';

    /** Text field bound to a typed HTML input type. */
    #[Prop('type', default: 'text', enum: InputType::class)]
    case input = 'input';
}
```

**How to derive these values** — read each `templates/components/*.blade.php` file:
- From `@props([...])`: extract prop name, default value, and backing enum (if `EnumName::case->value` pattern)
- `card` has no `@props()` → no `#[Prop]` attributes
- `form_group`'s `label` prop has no default → `default: ''` (empty string) with no enum

### 3. Integration Test Classes — Apply `#[CoversRoute]`

Apply `#[CoversRoute]` to each test class based on which `Route::*` constants it references.

| Test File | Attribute to add |
|---|---|
| `tests/Integration/HomeControllerTest.php` | `#[CoversRoute(Route::home)]` |
| `tests/Integration/AboutRouteTest.php` | `#[CoversRoute(Route::about)]` |
| `tests/Integration/LoginRouteTest.php` | `#[CoversRoute(Route::login)]` |
| `tests/Integration/RegisterRouteTest.php` | `#[CoversRoute(Route::register)]` |
| `tests/Integration/OpcacheStatusRouteTest.php` | `#[CoversRoute(Route::opcache_status)]` |
| `tests/Integration/OpcacheScriptsRouteTest.php` | `#[CoversRoute(Route::opcache_scripts)]` |
| `tests/Integration/RoutesRouteTest.php` | `#[CoversRoute(Route::routes)]` |
| `tests/Integration/StyleguideRouteTest.php` | `#[CoversRoute(Route::styleguide)]` |
| `tests/Integration/TRACE001Test.php` | No `#[CoversRoute]` — covers a requirement, not a route |
| `tests/Integration/BladeCacheTest.php` | No `#[CoversRoute]` — infrastructure test |
| `tests/Integration/HOT004Test.php` | No `#[CoversRoute]` — covers a requirement, not a route |

**How to derive** — read each test file body for `Route::caseName` references. Only apply if the test is structurally about that route (dispatches requests to it), not incidental references.

Example transformation for `RegisterRouteTest.php`:
```php
use ZeroToProd\Thryds\Attributes\CoversRoute;
use ZeroToProd\Thryds\Routes\Route;

#[CoversRoute(Route::register)]
final class RegisterRouteTest extends IntegrationTestCase
```

## File Checklist

| File | Action |
|---|---|
| `src/Blade/View.php` | Add `use` imports + `#[ExtendsLayout]`, `#[PageTitle]`, `#[UsesComponent]`, `#[ReceivesViewModel]` to each case |
| `src/Blade/Component.php` | Add `use` imports + `#[Prop]` to each case |
| `tests/Integration/HomeControllerTest.php` | Add `#[CoversRoute(Route::home)]` |
| `tests/Integration/AboutRouteTest.php` | Add `#[CoversRoute(Route::about)]` |
| `tests/Integration/LoginRouteTest.php` | Add `#[CoversRoute(Route::login)]` |
| `tests/Integration/RegisterRouteTest.php` | Add `#[CoversRoute(Route::register)]` |
| `tests/Integration/OpcacheStatusRouteTest.php` | Add `#[CoversRoute(Route::opcache_status)]` |
| `tests/Integration/OpcacheScriptsRouteTest.php` | Add `#[CoversRoute(Route::opcache_scripts)]` |
| `tests/Integration/RoutesRouteTest.php` | Add `#[CoversRoute(Route::routes)]` |
| `tests/Integration/StyleguideRouteTest.php` | Add `#[CoversRoute(Route::styleguide)]` |

## Verification

```bash
# Attributes are syntactically valid and types resolve
./run check:types

# Code style (new use imports sorted correctly)
./run check:style

# All existing tests still pass — no behavioral change
./run check:all
```

## Cross-Check: Attributes vs Templates

After applying, manually verify a sample to confirm attribute values match template reality:

```bash
# View::register should have: ExtendsLayout('base'), PageTitle('Register — Thryds'),
# UsesComponent(card, form_group, input, button), no ReceivesViewModel
# Verify against: templates/register.blade.php
#   @extends('base') ✓
#   @section('title', 'Register — Thryds') ✓
#   <x-card>, <x-form-group>, <x-input>, <x-button> ✓
#   No ViewModel use import ✓
```

This manual cross-check is a one-time validation. After Phase 3, inventory will derive the graph from attributes only, and Phase 5's `check:manifest` will enforce consistency permanently.
