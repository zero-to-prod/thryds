<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Routes\Route;

// TODO: [RequireRoutePatternConstRector] Route class 'ZeroToProd\Thryds\Attributes\CoversRoute' is missing a 'pattern' constant — define: public const string pattern = '/...'. See: utils/rector/docs/RequireRoutePatternConstRector.md
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
