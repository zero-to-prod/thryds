<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

/**
 * Blade component identifiers. Each case maps to templates/components/{value}.blade.php.
 *
 * Components are registered as <x-{value}> aliases in App::bootBlade().
 */
#[ClosedSet(
    Domain::blade_components,
    addCase: <<<TEXT
        1. Add enum case.
        2. Create templates/components/{case}.blade.php with @props.
        3. Add example to styleguide template.
    TEXT,
)]
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

    /** Blade view name used by compiler()->component(). */
    public function viewName(): string
    {
        return 'components.' . $this->value;
    }
}
