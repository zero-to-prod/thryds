<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use LogicException;
use ReflectionAttribute;
use ReflectionEnumUnitCase;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\DevOnly;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\Attributes\RouteParam;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Controllers\OpcacheScriptsHandler;
use ZeroToProd\Thryds\Controllers\OpcacheStatusHandler;
use ZeroToProd\Thryds\Controllers\RegisterController;
use ZeroToProd\Thryds\Controllers\RouteManifestHandler;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Routes\Actions\Form;
use ZeroToProd\Thryds\Routes\Actions\StaticView;
use ZeroToProd\Thryds\Routes\Actions\Validated;
use ZeroToProd\Thryds\UI\Domain;
use ZeroToProd\Thryds\ViewModels\RegisterViewModel;

#[ClosedSet(
    Domain::url_routes,
    addCase: <<<TEXT
    1. Add entry to thryds.yaml routes section.
    2. Run ./run sync:manifest.
    3. Implement controller logic (if controller route).
    4. Run ./run fix:all.
    TEXT
)]
enum Route: string
{
    #[RouteOperation(
        HttpMethod::GET,
        new StaticView(View::home),
        'Marketing home page'
    )]
    case home = '/';

    #[RouteOperation(
        HttpMethod::GET,
        new StaticView(View::about),
        'Company and product information'
    )]
    case about = '/about';

    #[RouteOperation(
        HttpMethod::GET,
        new StaticView(View::login),
        'User authentication form'
    )]
    case login = '/login';

    #[RouteOperation(
        HttpMethod::GET,
        new Form(
            View::register,
            controller: RegisterController::class,
            request: RegisterRequest::class,
            view_model: RegisterViewModel::class,
        ),
        'New user registration form',
    )]
    #[RouteOperation(
        HttpMethod::POST,
        new Validated(
            controller: RegisterController::class,
            request: RegisterRequest::class,
            view_model: RegisterViewModel::class,
        ),
        null,
    )]
    case register = '/register';

    #[DevOnly]
    #[RouteOperation(
        HttpMethod::GET,
        OpcacheStatusHandler::class,
        'OPcache runtime statistics'
    )]
    case opcache_status = '/_opcache/status';

    #[DevOnly]
    #[RouteOperation(
        HttpMethod::GET,
        OpcacheScriptsHandler::class,
        'Scripts loaded in OPcache'
    )]
    case opcache_scripts = '/_opcache/scripts';

    #[DevOnly]
    #[RouteOperation(
        HttpMethod::GET,
        new StaticView(View::styleguide),
        'UI component and design token reference'
    )]
    case styleguide = '/_styleguide';

    #[DevOnly]
    #[RouteOperation(
        HttpMethod::GET,
        RouteManifestHandler::class,
        'Machine-readable manifest of all registered routes'
    )]
    case routes = '/_routes';

    public function isDevOnly(): bool
    {
        /** @var array<string, bool> $cache */
        static $cache = [];

        return $cache[$this->name] ??= !empty(
            new ReflectionEnumUnitCase(self::class, $this->name)->getAttributes(DevOnly::class)
        );
    }

    /** Returns the route-level description from the first #[RouteOperation] with a non-null description. */
    public function description(): string
    {
        /** @var array<string, string> $cache */
        static $cache = [];

        return $cache[$this->name] ??= (function (): string {
            foreach ($this->operations() as $op) {
                if ($op->description !== null) {
                    return $op->description;
                }
            }
            throw new LogicException("Route::{$this->name} has no #[RouteOperation] with a description.");
        })();
    }

    /** @return RouteOperation[] HTTP operations declared on this route via #[RouteOperation]. */
    public function operations(): array
    {
        /** @var array<string, RouteOperation[]> $cache */
        static $cache = [];

        return $cache[$this->name] ??= array_map(
            static fn(ReflectionAttribute $ReflectionAttribute): RouteOperation => $ReflectionAttribute->newInstance(),
            new ReflectionEnumUnitCase(self::class, $this->name)
                ->getAttributes(RouteOperation::class),
        );
    }

    /** Returns the View from the first action that carries a View, or null. */
    public function rendersView(): ?View
    {
        /** @var array<string, ?View> $cache */
        static $cache = [];

        if (!array_key_exists($this->name, array: $cache)) {
            $cache[$this->name] = null;
            foreach ($this->operations() as $op) {
                if ($op->action instanceof StaticView || $op->action instanceof Form) {
                    $cache[$this->name] = $op->action->View;
                    break;
                }
            }
        }

        return $cache[$this->name];
    }

    /** Returns the controller from the first action that carries a controller, or null. */
    public function controller(): ?object
    {
        /** @var array<string, ?object> $cache */
        static $cache = [];

        if (!array_key_exists($this->name, array: $cache)) {
            $cache[$this->name] = null;
            foreach ($this->operations() as $op) {
                $class = match (true) {
                    $op->action instanceof Form, $op->action instanceof Validated => $op->action->controller,
                    is_string($op->action)                                        => $op->action,
                    is_array($op->action)                                         => $op->action[0],
                    default                                                       => null,
                };
                if ($class !== null) {
                    $cache[$this->name] = new $class();
                    break;
                }
            }
        }

        return $cache[$this->name];
    }

    /** @return string[] Parameter names declared via #[RouteParam] on this route case. */
    public function params(): array
    {
        /** @var array<string, string[]> $cache */
        static $cache = [];

        return $cache[$this->name] ??= array_map(
            static fn(ReflectionAttribute $ReflectionAttribute): string => $ReflectionAttribute->newInstance()->name,
            new ReflectionEnumUnitCase(self::class, $this->name)
                ->getAttributes(RouteParam::class),
        );
    }

    /**
     * @param array<string, string> $params
     * @param array<string, string> $query
     */
    public function with(array $params = [], array $query = []): RouteUrl
    {
        return new RouteUrl(Route: $this, params: $params, query: $query);
    }
}
