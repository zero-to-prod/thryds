<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

/**
 * Blade template identifiers. Each case maps to templates/{value}.blade.php.
 *
 * Pass the value to Blade::make(view: View::name->value).
 * For views with view-model data, pass the ViewModel via view_key as the array key.
 */
#[ClosedSet(
    Domain::blade_templates,
    addCase: '1. Add enum case. 2. Create templates/{case}.blade.php. 3. Add render call in generate-preload.php. 4. Add to production-checklist.php view_data.',
)]
enum View: string
{
    case about = 'about';
    case error = 'error';
    case home = 'home';
}
