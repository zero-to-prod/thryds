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
