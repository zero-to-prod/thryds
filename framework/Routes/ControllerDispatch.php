<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes;

use LogicException;
use ReflectionClass;
use ZeroToProd\Framework\Attributes\HandlesMethod;
use ZeroToProd\Framework\Attributes\Infrastructure;

/**
 * Resolves a controller class-string to a callable via invocation or method-dispatch attribute.
 *
 * @see HandlesMethod
 */
#[Infrastructure]
readonly class ControllerDispatch
{
    /**
     * @param class-string $class
     *
     * @return callable
     */
    public static function resolve(string $class, HttpMethod $HttpMethod): callable
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
}
