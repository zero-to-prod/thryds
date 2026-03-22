<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use Closure;
use Laminas\Diactoros\Response\HtmlResponse;
use League\Route\Router;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ZeroToProd\Thryds\Attributes\HandlesMethod;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\Attributes\ValidatesRequest;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Requests\InputField;
use ZeroToProd\Thryds\Validation\Validator;

#[Infrastructure]
readonly class RouteRegistrar
{
    public static function register(Router $Router, Config $Config): void
    {
        foreach (Route::cases() as $Route) {
            if ($Route->isDevOnly() && $Config->isProduction()) {
                continue;
            }

            foreach ($Route->operations() as $op) {
                $Router->map(
                    $op->HttpMethod->value,
                    $Route->value,
                    handler: self::handler($Route, RouteOperation: $op, controller: $Route->controller()),
                );
            }
        }
    }

    /** Dispatch to the handler strategy declared on the #[RouteOperation]. */
    private static function handler(Route $Route, RouteOperation $RouteOperation, ?object $controller): callable
    {
        return match ($RouteOperation->HandlerStrategy) {
            HandlerStrategy::static_view => self::staticView($Route),
            HandlerStrategy::controller  => self::controllerHandler($controller, $RouteOperation),
            HandlerStrategy::form        => self::formView($Route, self::validatesRequest($controller)),
            HandlerStrategy::validated   => self::withValidation(
                $Route,
                self::validatesRequest($controller),
                handler: self::controllerHandler($controller, $RouteOperation),
            ),
        };
    }

    /** Render a Blade view with no controller. */
    private static function staticView(Route $Route): Closure
    {
        return static fn(): ResponseInterface => new HtmlResponse(
            html: blade()->make(view: ($Route->rendersView()
                ?? throw new LogicException("Route::{$Route->name} has HandlerStrategy::static_view but no #[RendersView]."))->value)->render(),
        );
    }

    /**
     * Resolve the controller callable — either __invoke or a #[HandlesMethod]-annotated method.
     *
     * @return callable
     */
    private static function controllerHandler(?object $controller, RouteOperation $RouteOperation): callable
    {
        if ($controller === null) {
            throw new LogicException("RouteOperation declares HandlerStrategy::{$RouteOperation->HandlerStrategy->name} but route has no #[HandledBy] controller.");
        }

        if (is_callable(value: $controller)) {
            return $controller;
        }

        /** @phpstan-ignore return.type (array{object, string} from resolveMethod satisfies callable at runtime) */
        return self::resolveMethod($controller, HttpMethod: $RouteOperation->HttpMethod);
    }

    /** Read the #[ValidatesRequest] attribute from a controller, or throw if absent. */
    private static function validatesRequest(?object $controller): ValidatesRequest
    {
        if ($controller === null) {
            throw new LogicException('HandlerStrategy::form or ::validated requires a #[HandledBy] controller with #[ValidatesRequest].');
        }

        $attrs = new ReflectionClass(objectOrClass: $controller)->getAttributes(ValidatesRequest::class);

        return $attrs !== []
            ? $attrs[0]->newInstance()
            : throw new LogicException($controller::class . ' must declare #[ValidatesRequest] for the form/validated handler strategy.');
    }

    /**
     * Resolve a controller method by its declared #[HandlesMethod] attribute.
     *
     * @return array{object, string}
     */
    private static function resolveMethod(object $controller, HttpMethod $HttpMethod): array
    {
        foreach (new ReflectionClass(objectOrClass: $controller)->getMethods() as $method) {
            $attrs = $method->getAttributes(HandlesMethod::class);
            if ($attrs !== [] && $attrs[0]->newInstance()->HttpMethod === $HttpMethod) {
                return [$controller, $method->getName()];
            }
        }

        throw new LogicException(
            $controller::class . ' has no method with #[HandlesMethod(' . $HttpMethod->name . ')]. '
            . 'Add #[HandlesMethod(HttpMethod::' . $HttpMethod->name . ')] to the handler method.'
        );
    }

    /** Render an empty form view derived from #[ValidatesRequest] and #[RendersView]. */
    private static function formView(Route $Route, ValidatesRequest $ValidatesRequest): Closure
    {
        return static fn(): ResponseInterface => self::renderForm($Route, $ValidatesRequest, data: []);
    }

    /** Wrap a POST handler with attribute-driven validation and error re-rendering. */
    private static function withValidation(Route $Route, ValidatesRequest $ValidatesRequest, callable $handler): Closure
    {
        return static function (ServerRequestInterface $ServerRequestInterface) use ($Route, $ValidatesRequest, $handler): ResponseInterface {
            $requestObject = $ValidatesRequest->request::from($ServerRequestInterface->getParsedBody());

            $errors = Validator::validate(model: $requestObject);
            if ($errors === []) {
                return $handler($requestObject);
            }

            return self::renderForm($Route, $ValidatesRequest, data: [...$requestObject->toArray(), $ValidatesRequest->view_model::errors => $errors]);
        };
    }

    /** @param array<string, mixed> $data */
    private static function renderForm(Route $Route, ValidatesRequest $ValidatesRequest, array $data): HtmlResponse
    {
        $View = $Route->rendersView()
            ?? throw new LogicException("Route::{$Route->name} has #[ValidatesRequest] but no #[RendersView].");
        $view_model_class = $ValidatesRequest->view_model;

        return new HtmlResponse(
            html: blade()->make(view: $View->value, data: [
                $view_model_class::view_key => $view_model_class::from($data),
                InputField::fields => InputField::reflect(class: $ValidatesRequest->request),
            ])->render()
        );
    }

}
