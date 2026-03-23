<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes\Actions;

use BackedEnum;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stringable;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Routes\ControllerDispatch;
use ZeroToProd\Framework\Routes\HttpMethod;
use ZeroToProd\Framework\Validation\Validator;

/** Validate the request body; re-render on error, delegate on success. */
#[ActionStrategy]
#[Infrastructure]
final readonly class Validated implements Stringable
{
    /**
     * @param class-string $controller
     * @param class-string $request
     * @param class-string $view_model
     */
    public function __construct(
        public string $controller,
        public string $request,
        public string $view_model,
    ) {}

    public function toCallable(BackedEnum $BackedEnum, HttpMethod $HttpMethod): callable
    {
        $handler = ControllerDispatch::resolve(class: $this->controller, HttpMethod: $HttpMethod);
        $Form = self::findFormAction($BackedEnum);

        return function (ServerRequestInterface $ServerRequestInterface) use ($Form, $handler): ResponseInterface {
            $requestObject = $this->request::from($ServerRequestInterface->getParsedBody());

            $errors = Validator::validate(model: $requestObject);
            if ($errors === []) {
                return $handler($requestObject);
            }

            return $Form->render(data: [...$requestObject->toArray(), $this->view_model::errors => $errors]);
        };
    }

    /** Resolve the sibling Form action on a route case. Guaranteed to exist by boot-time assertion. */
    private static function findFormAction(BackedEnum $BackedEnum): Form
    {
        foreach (Route::on($BackedEnum) as $RouteOp) {
            if ($RouteOp->action instanceof Form) {
                return $RouteOp->action;
            }
        }

        throw new LogicException($BackedEnum::class . '::' . $BackedEnum->name . ' has no Form action.');
    }

    public function __toString(): string
    {
        return 'Validated';
    }
}
