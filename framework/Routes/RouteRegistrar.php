<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes;

use BackedEnum;
use Closure;
use League\Route\Router;
use LogicException;
use ZeroToProd\Framework\Attributes\Guarded;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Attributes\Middleware;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Config;
use ZeroToProd\Framework\Routes\Actions\Form;
use ZeroToProd\Framework\Routes\Actions\Validated;

#[Infrastructure]
readonly class RouteRegistrar
{
    /**
     * Returns the route providers registered at boot time.
     *
     * @param list<class-string<BackedEnum>>|null $set Pass to store; omit to read.
     * @return list<class-string<BackedEnum>>
     */
    public static function providers(?array $set = null): array
    {
        /** @var list<class-string<BackedEnum>> $stored */
        static $stored = [];

        if ($set !== null) {
            $stored = $set;
        }

        return $stored;
    }

    /** @param list<class-string<BackedEnum>> $routeProviders */
    public static function register(Router $Router, Config $Config, array $routeProviders): void
    {
        self::providers(set: $routeProviders);

        foreach ($routeProviders as $routeProvider) {
            foreach ($routeProvider::cases() as $BackedEnum) {
                if (Guarded::of($BackedEnum)?->passes($Config) === false) {
                    continue;
                }

                $middleware_stack = Middleware::of($BackedEnum);

                $operations = Route::on($BackedEnum);

                self::assertFormPairing($BackedEnum, $operations);

                foreach ($operations as $Route) {
                    $Route = $Router->map(
                        $Route->method()->value,
                        (string) $BackedEnum->value,
                        handler: self::handler($BackedEnum, $Route),
                    );

                    foreach ($middleware_stack as $middlewareClass) {
                        $Route->lazyMiddleware(middleware: $middlewareClass);
                    }
                }
            }
        }
    }

    /** Dispatch to the handler strategy declared on the route operation attribute. */
    private static function handler(BackedEnum $BackedEnum, Route $Route): callable
    {
        $action = $Route->action;

        if (is_object(value: $action) && method_exists(object_or_class: $action, method: 'toCallable')) {
            return $action->toCallable($BackedEnum, $Route->method());
        }

        return match (true) {
            $action instanceof Closure => $action,
            is_string(value: $action)  => ControllerDispatch::resolve(class: $action, HttpMethod: $Route->method()),
            is_array(value: $action)   => new $action[0]()->{$action[1]}(...),
            default                    => throw new LogicException('Unresolvable route action: ' . get_debug_type(value: $action)),
        };
    }

    /**
     * Verify that every Validated action on a route case has a sibling Form action for error re-rendering.
     *
     * @param Route[] $operations
     */
    private static function assertFormPairing(BackedEnum $BackedEnum, array $operations): void
    {
        $has_validated = false;
        $has_form = false;

        foreach ($operations as $Route) {
            if ($Route->action instanceof Validated) {
                $has_validated = true;
            }
            if ($Route->action instanceof Form) {
                $has_form = true;
            }
        }

        if ($has_validated && !$has_form) {
            throw new LogicException(
                $BackedEnum::class . '::' . $BackedEnum->name
                . ' has a Validated action but no Form action to re-render on validation failure.'
                . ' Add a #[Route] with a Form action on this case.'
            );
        }
    }

}
