# ADR-008: Component-Driven Design System Optimized for AI Agents

## Status
Proposed

## Context
The backend is structured so that an AI agent can discover conventions, follow checklists, and get machine feedback on every violation. The frontend has none of this. Templates use raw HTML with ad-hoc Tailwind classes — an agent building a new page must invent markup and styling from scratch each time, producing inconsistent output that no automated check can catch.

Tailwind CSS 4, Alpine.js, and HTMX are already in place. The missing layer is a constrained component system that gives agents (and humans) a closed set of building blocks with a fixed visual contract.

## Decision

### 1. Blade components as the atomic unit
Create `templates/components/` with one file per component. Each component is a Blade anonymous component (`x-button`, `x-input`, `x-card`, `x-form-group`, etc.) that accepts typed props and renders consistent markup internally.

```
templates/components/
    button.blade.php
    input.blade.php
    form-group.blade.php
    card.blade.php
    alert.blade.php
    nav-link.blade.php
```

File name = component name = tag name. `x-button` lives in `button.blade.php`. No indirection, no barrel files.

### 2. Variant props over utility class strings
Components accept semantic props — not raw Tailwind classes. The component maps variants to classes internally.

```blade
{{-- Caller writes this --}}
<x-button variant="primary" size="sm">Save</x-button>

{{-- NOT this --}}
<button class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-1 px-3 rounded">Save</button>
```

Variant values are finite and discoverable. An agent choosing `variant="primary"` is a closed decision. An agent constructing a Tailwind class string is an open-ended decision with no validation.

### 3. Constrained Tailwind palette via @theme
Define a `@theme` block in `resources/css/app.css` that limits available colors, spacing, and typography to the design system's values. This replaces Tailwind's 220+ default colors with a curated set.

```css
@import "tailwindcss";

@theme {
    --color-primary: #3b82f6;
    --color-primary-hover: #2563eb;
    --color-danger: #ef4444;
    --color-danger-hover: #dc2626;
    --color-surface: #ffffff;
    --color-surface-alt: #f9fafb;
    --color-border: #e5e7eb;
    --color-text: #111827;
    --color-text-muted: #6b7280;

    --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
    --font-mono: 'JetBrains Mono', ui-monospace, monospace;

    --radius-sm: 0.375rem;
    --radius-md: 0.5rem;
    --radius-lg: 0.75rem;
}
```

Fewer tokens means fewer wrong choices. An agent picks from 8 colors, not 220.

### 4. Component enum with ClosedSet checklist
Add a `Component` enum following the same pattern as `View` and `Route`:

```php
#[ClosedSet(
    Domain::blade_components,
    addCase: '1. Add enum case. 2. Create templates/components/{case}.blade.php with @props. 3. Add example to styleguide template. 4. Add to component test.',
)]
enum Component: string
{
    case button = 'button';
    case input = 'input';
    case form_group = 'form-group';
    case card = 'card';
    case alert = 'alert';
    case nav_link = 'nav-link';
}
```

An agent reads the enum to see all available components and follows `addCase` to create new ones.

### 5. Styleguide template as living documentation
Register a dev-only route (`/_styleguide`) that renders every component in every variant. This serves as a copy-paste reference for agents building new pages — one file shows all valid compositions.

```php
case styleguide = '/_styleguide';
```

### 6. Rector rule enforcing component usage
Add a Rector or Blade linter rule that warns when raw HTML tags are used where a component exists:

| Raw HTML | Expected component |
|----------|-------------------|
| `<button>` | `<x-button>` |
| `<input>` | `<x-input>` |
| `<a href=...>` (navigation) | `<x-nav-link>` |

This closes the feedback loop: an agent writes `<button>`, runs `./run check:all`, gets told to use `<x-button>`. Same pattern as `ForbidHardcodedRouteStringRector`.

## How the pieces compose

A page template under this system reads as a composition of named components with semantic props:

```blade
@extends('base')

@section('body')
    <x-card>
        <h1>Login</h1>
        <form method="post" action="{{ Route::login->value }}">
            <x-form-group label="Email">
                <x-input type="email" name="email" required />
            </x-form-group>
            <x-form-group label="Password">
                <x-input type="password" name="password" required />
            </x-form-group>
            <x-button variant="primary" type="submit">Login</x-button>
        </form>
    </x-card>
@endsection
```

An agent building this page made zero styling decisions. Every visual choice is encapsulated inside the component files.

## Implementation priority

| Phase | What | Why first |
|-------|------|-----------|
| 1 | `@theme` block + base components (`x-button`, `x-input`, `x-form-group`, `x-card`, `x-alert`) | Constrains the palette and provides the building blocks every page needs |
| 2 | `Component` enum with `#[ClosedSet]` checklist | Makes components discoverable and extensible via the same pattern as routes and views |
| 3 | Styleguide template at `/_styleguide` | Gives agents a single reference for all valid compositions |
| 4 | Blade linter rule enforcing component usage | Closes the feedback loop — agents self-correct without human review |

## Consequences

- **Agents compose, they don't invent.** Building a page becomes assembling named components with constrained props. No raw HTML, no ad-hoc class strings.
- **Visual consistency is structural.** Changing a button's appearance means editing one file (`button.blade.php`), not finding every `<button>` across all templates.
- **The decision space shrinks.** Fewer colors, fewer components, semantic variants — agents make fewer choices and more of them are correct.
- **Same feedback loop as the backend.** Write code, run `check:all`, get told exactly what to fix. The pattern proven by 51 Rector rules extends to the view layer.
- **Maintenance cost.** Each component needs props documentation and a styleguide entry. The `Component` enum and Rector rule add enforcement overhead — but the payoff is zero-cost consistency on every commit.
- **Blade component registration.** Anonymous Blade components require configuring the component path in `App::bootBlade()`. This is a one-time setup cost.
