<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use BackedEnum;
use Closure;
use Laminas\Diactoros\Response\HtmlResponse;
use League\Route\Router;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Guarded;
use ZeroToProd\Thryds\Attributes\HandlesMethod;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\Middleware;
use ZeroToProd\Thryds\Attributes\Route;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Requests\InputField;
use ZeroToProd\Thryds\Routes\Actions\Form;
use ZeroToProd\Thryds\Routes\Actions\StaticView;
use ZeroToProd\Thryds\Routes\Actions\Validated;
use ZeroToProd\Thryds\Validation\Validator;

#[Infrastructure]
readonly class RouteRegistrar
{
    public static function register(Router $Router, Config $Config): void
    {
        foreach (RouteSource::cases() as $RouteSource) {
            foreach ($RouteSource->enumClass()::cases() as $BackedEnum) {
                if (Guarded::of($BackedEnum)?->passes($Config) === false) {
                    continue;
                }

                $middleware_stack = Middleware::of($BackedEnum);

                foreach (Route::on($BackedEnum) as $Route) {
                    $Route = $Router->map(
                        $Route->HttpMethod->value,
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

        return match (true) {
            $action instanceof StaticView  => self::staticView(StaticView: $action),
            $action instanceof Form        => self::formView(Form: $action),
            $action instanceof Validated   => self::withValidation(
                $BackedEnum,
                Validated: $action,
                handler: self::resolveCallable($action->controller, $Route->HttpMethod),
            ),
            $action instanceof Closure     => $action,
            is_string(value: $action)             => self::resolveCallable(class: $action, HttpMethod: $Route->HttpMethod),
            is_array(value: $action)              => new $action[0]()->{$action[1]}(...),
        };
    }

    /** Render a Blade view with no controller. */
    private static function staticView(StaticView $StaticView): Closure
    {
        return static fn(): ResponseInterface => new HtmlResponse(
            html: blade()->make(view: $StaticView->View->value)->render(),
        );
    }

    /**
     * Resolve a class-string to a callable — invokable or method-dispatch-annotated.
     *
     * @param class-string $class
     * @return callable
     */
    private static function resolveCallable(string $class, HttpMethod $HttpMethod): callable
    {
        $controller = new $class();

        if (is_callable(value: $controller)) {
            return $controller;
        }

        foreach (new ReflectionClass(objectOrClass: $controller)->getMethods() as $method) {
            $attrs = $method->getAttributes(HandlesMethod::class);
            if ($attrs !== [] && $attrs[0]->newInstance()->HttpMethod === $HttpMethod) {
                return $controller->{$method->getName()}(...);
            }
        }

        throw new LogicException(
            $class . ' has no method with #[HandlesMethod(' . $HttpMethod->name . ')]. '
            . 'Add #[HandlesMethod(HttpMethod::' . $HttpMethod->name . ')] to the handler method.'
        );
    }

    /** Render an empty form view derived from the Form action. */
    private static function formView(Form $Form): Closure
    {
        return static fn(): ResponseInterface => self::renderForm($Form, data: []);
    }

    /** Wrap a POST handler with action-driven validation and error re-rendering. */
    private static function withValidation(BackedEnum $BackedEnum, Validated $Validated, callable $handler): Closure
    {
        return static function (ServerRequestInterface $ServerRequestInterface) use ($BackedEnum, $Validated, $handler): ResponseInterface {
            $requestObject = $Validated->request::from($ServerRequestInterface->getParsedBody());

            $errors = Validator::validate(model: $requestObject);
            if ($errors === []) {
                return $handler($requestObject);
            }

            // Find the Form action on the same route to get the View for re-rendering.
            $form_action = null;
            foreach (Route::on($BackedEnum) as $RouteOp) {
                if ($RouteOp->action instanceof Form) {
                    $form_action = $RouteOp->action;
                    break;
                }
            }

            if ($form_action === null) {
                throw new LogicException($BackedEnum::class . '::' . $BackedEnum->name . ' has a Validated action but no Form action to re-render on validation failure.');
            }

            return self::renderForm(Form: $form_action, data: [...$requestObject->toArray(), $Validated->view_model::errors => $errors]);
        };
    }

    /** @param array<string, mixed> $data */
    private static function renderForm(Form $Form, array $data): HtmlResponse
    {
        $view_model_class = $Form->view_model;

        return new HtmlResponse(
            html: blade()->make(view: $Form->View->value, data: [
                $view_model_class::view_key => $view_model_class::from($data),
                InputField::fields => InputField::reflect(class: $Form->request),
            ])->render()
        );
    }

}
