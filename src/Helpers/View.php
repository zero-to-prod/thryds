<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use ZeroToProd\Thryds\Controllers\HomeController;
use ZeroToProd\Thryds\Routes\WebRoutes;

/**
 * Blade template identifiers. Each case maps to templates/{value}.blade.php.
 *
 * Pass the value to Blade::make(view: View::name->value).
 * For views with view-model data, pass the ViewModel via view_key as the array key.
 */
#[ClosedSet(domain: 'Blade templates', used_in: [[WebRoutes::class, 'register'], [HomeController::class, '__invoke']])]
enum View: string
{
    case about = 'about';
    case error = 'error';
    case home = 'home';
}
