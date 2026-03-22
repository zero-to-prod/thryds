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

    /** Resolve handler: #[HandledBy] controller takes priority, then #[RendersView] closure. */
    private static function handler(Route $Route, RouteOperation $RouteOperation, ?object $controller): callable
    {
        if ($controller !== null) {
            $callable = is_callable(value: $controller)
                ? $controller
                : self::resolveMethod($controller, HttpMethod: $RouteOperation->HttpMethod);

            if ($RouteOperation->HttpMethod === HttpMethod::POST) {
                $attrs = new ReflectionClass(objectOrClass: $controller)->getAttributes(ValidatesRequest::class);
                if ($attrs !== []) {
                    /** @phpstan-ignore argument.type (invokable object satisfies callable at runtime) */
                    return self::withValidation($Route, $attrs[0]->newInstance(), handler: $callable);
                }
            }

            /** @phpstan-ignore return.type (invokable object satisfies callable at runtime) */
            return $callable;
        }

        return fn(): ResponseInterface => new HtmlResponse(
            html: blade()->make(view: ($Route->rendersView()
                ?? throw new LogicException("Route::{$Route->name} has no #[HandledBy] controller and no #[RendersView]."))->value)->render(),
        );
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

    /** Wrap a POST handler with attribute-driven validation and error re-rendering. */
    private static function withValidation(Route $Route, ValidatesRequest $ValidatesRequest, callable $handler): Closure
    {
        return static function (ServerRequestInterface $ServerRequestInterface) use ($Route, $ValidatesRequest, $handler): ResponseInterface {
            $request_class = $ValidatesRequest->request;
            $requestObject = $request_class::from($ServerRequestInterface->getParsedBody());

            $errors = Validator::validate(model: $requestObject);
            if ($errors === []) {
                return $handler($requestObject);
            }

            $View = $Route->rendersView()
                ?? throw new LogicException("Route::{$Route->name} has #[ValidatesRequest] but no #[RendersView].");
            $view_model_class = $ValidatesRequest->view_model;

            return new HtmlResponse(
                html: blade()->make(view: $View->value, data: [
                    $view_model_class::view_key => $view_model_class::from([...$requestObject->toArray(), $view_model_class::errors => $errors]),
                    InputField::fields => InputField::reflect(class: $request_class),
                ])->render()
            );
        };
    }

}
