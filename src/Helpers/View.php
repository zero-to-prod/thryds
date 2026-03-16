<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

/**
 * Blade template identifiers. Each constant maps to templates/{value}.blade.php.
 *
 * Pass the constant to Blade::make(view: View::name).
 * For views with view-model data, pass the ViewModel via short_class_name() as the array key.
 */
readonly class View
{
    public const string about = 'about';
    public const string error = 'error';
    public const string home = 'home';
}
