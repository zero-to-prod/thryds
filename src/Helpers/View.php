<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

/**
 * Blade template identifiers. Each case maps to templates/{value}.blade.php.
 *
 * Pass the value to Blade::make(view: View::name->value).
 * For views with view-model data, pass the ViewModel via view_key as the array key.
 */
enum View: string
{
    case about = 'about';
    case error = 'error';
    case home = 'home';
}
