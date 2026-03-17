<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

/**
 * Blade component identifiers. Each case maps to templates/components/{value}.blade.php.
 *
 * Components are registered as <x-{value}> aliases in App::bootBlade().
 */
#[ClosedSet(
    Domain::blade_components,
    addCase: '1. Add enum case. 2. Create templates/components/{case}.blade.php with @props. 3. Add example to styleguide template.',
)]
enum Component: string
{
    case alert = 'alert';
    case button = 'button';
    case card = 'card';
    case form_group = 'form-group';
    case input = 'input';

    /** Blade view name used by compiler()->component(). */
    public function viewName(): string
    {
        return 'components.' . $this->value;
    }
}
