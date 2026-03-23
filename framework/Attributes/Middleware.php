<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionEnumUnitCase;

/**
 * Declares PSR-15 middleware for a route or route file.
 *
 * When applied at the enum class level, all cases inherit the middleware.
 * Case-level middleware is additive — class-level runs first, then case-level.
 *
 * @param list<class-string<MiddlewareInterface>> $middleware
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
readonly class Middleware
{
    use AttributeCache;

    /** @var list<class-string<MiddlewareInterface>> */
    public array $middleware;

    /** @param class-string<MiddlewareInterface> ...$middleware */
    public function __construct(string ...$middleware)
    {
        $this->middleware = array_values(array: $middleware);
    }

    /**
     * Resolve the full middleware stack for a route case: class-level then case-level.
     *
     * @return list<class-string<MiddlewareInterface>>
     */
    public static function of(BackedEnum $BackedEnum): array
    {
        return self::cached('of', $BackedEnum::class . '::' . $BackedEnum->name, static function () use ($BackedEnum): array {
            $ReflectionEnumUnitCase = new ReflectionEnumUnitCase($BackedEnum::class, $BackedEnum->name);
            $stack = [];

            // Class-level middleware (inherited by all cases).
            foreach ($ReflectionEnumUnitCase->getEnum()->getAttributes(self::class) as $attr) {
                $stack = [...$stack, ...$attr->newInstance()->middleware];
            }

            // Case-level middleware (additive).
            foreach ($ReflectionEnumUnitCase->getAttributes(self::class) as $attr) {
                $stack = [...$stack, ...$attr->newInstance()->middleware];
            }

            return $stack;
        });
    }
}
