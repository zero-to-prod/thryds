<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use Laminas\Diactoros\Response\HtmlResponse;
use League\Route\Router;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\Config;

readonly class RouteRegistrar
{
    /** Namespace prefix for controller discovery, derived from this class's own namespace. */
    private const string CONTROLLER_NAMESPACE = 'ZeroToProd\\Thryds\\Controllers\\';

    public static function register(Router $Router, Config $Config): void
    {
        $controllers = self::discoverControllers();

        foreach (Route::cases() as $Route) {
            if ($Route->isDevOnly() && $Config->isProduction()) {
                continue;
            }

            foreach ($Route->operations() as $op) {
                $Router->map(
                    $op->HttpMethod->value,
                    $Route->value,
                    handler: self::handler($Route, RouteOperation: $op, controller: $controllers[$Route->name] ?? null),
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
            $fqcn = self::CONTROLLER_NAMESPACE . basename(path: $file, suffix: '.php');

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
    private static function handler(Route $Route, RouteOperation $RouteOperation, ?object $controller): callable
    {
        if ($controller !== null) {
            /** @phpstan-ignore return.type (invokable object satisfies callable at runtime) */
            return is_callable(value: $controller) ? $controller : [$controller, strtolower($RouteOperation->HttpMethod->value)];
        }

        return fn(): ResponseInterface => new HtmlResponse(
            html: blade()->make(view: ($Route->rendersView()
                ?? throw new LogicException("Route::{$Route->name} has no #[HandlesRoute] controller and no #[RendersView]."))->value)->render(),
        );
    }

}
