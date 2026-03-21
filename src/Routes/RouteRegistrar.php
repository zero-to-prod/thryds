<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use Laminas\Diactoros\Response\HtmlResponse;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\Config;

readonly class RouteRegistrar
{
    public static function register(Router $Router, Config $Config): void
    {
        $controllers = self::discoverControllers();

        foreach (Route::cases() as $Route) {
            if ($Route->isDevOnly() && $Config->isProduction()) {
                continue;
            }

            foreach ($Route->operations() as $op) {
                // TODO: [RequireRouteEnumInMapCallRector] Enumerations define sets — route pattern must use Route::case->value. Found '(expression)' instead.
                $Router->map(
                    $op->HttpMethod->value,
                    $Route->value,
                    handler: self::handler($Route, $op, $controllers[$Route->name] ?? null),
                );
            }
        }
    }

    /**
     * Scan src/Controllers/ for classes carrying #[HandlesRoute] and return instances keyed by Route case name.
     *
     * @return array<string, object>
     */
    private static function discoverControllers(): array
    {
        $controllers = [];
        $dir = dirname(__DIR__) . '/Controllers';

        foreach (glob($dir . '/*.php') ?: [] as $file) {
            // TODO: [ForbidHardcodedNamespacePrefixRector] Declarations over hardcoding — namespace prefix should be passed in as configuration.
            $fqcn = 'ZeroToProd\\Thryds\\Controllers\\' . basename(path: $file, suffix: '.php');

            if (!class_exists(class: $fqcn)) {
                continue;
            }
            $attrs = new ReflectionClass(objectOrClass: $fqcn)->getAttributes(HandlesRoute::class);

            if ($attrs === []) {
                continue;
            }
            $controllers[$attrs[0]->newInstance()->Route->name] = new $fqcn();
        }

        return $controllers;
    }

    /** Resolve handler: #[HandlesRoute] controller takes priority, then #[RendersView] closure. */
    private static function handler(Route $Route, RouteOperation $op, ?object $controller): callable
    {
        if ($controller !== null) {
            /** @phpstan-ignore return.type (invokable object satisfies callable at runtime) */
            return is_callable($controller) ? $controller : [$controller, strtolower($op->HttpMethod->value)];
        }

        $View = $Route->rendersView()
            ?? throw new \LogicException("Route::{$Route->name} has no #[HandlesRoute] controller and no #[RendersView].");

        return fn(): ResponseInterface => new HtmlResponse(
            html: blade()->make(view: $View->value)->render(),
        );
    }

}
