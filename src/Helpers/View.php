<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use ZeroToProd\Thryds\Controllers\HomeController;
use ZeroToProd\Thryds\Routes\WebRoutes;
use ZeroToProd\Thryds\Tests\Integration\BladeCacheTest;

/**
 * Blade template identifiers. Each case maps to templates/{value}.blade.php.
 *
 * Pass the value to Blade::make(view: View::name->value).
 * For views with view-model data, pass the ViewModel via view_key as the array key.
 */
#[SourceOfTruth(
    for: 'Blade template names',
    consumers: [
        HomeController::class,
        WebRoutes::class,
        'public/index.php',
        'scripts/generate-preload.php',
        'scripts/production-checklist.php',
        BladeCacheTest::class,
    ],
    addCase: '1. Add enum case. 2. Create templates/{case}.blade.php. 3. Add render call in generate-preload.php. 4. Add to production-checklist.php view_data.',
)]
#[ClosedSet(domain: 'Blade templates', used_in: [[WebRoutes::class, 'register'], [HomeController::class, '__invoke']])]
enum View: string
{
    case about = 'about';
    case error = 'error';
    case home = 'home';
}
