<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use Throwable;

/**
 * Declares that a method handles exceptions to the specified type.
 *
 * Applied to methods on an exception handler class. At dispatch time, the method
 * whose declared exception type is the most specific match for the thrown exception
 * is invoked. More specific types take priority over general ones.
 *
 * @example
 * #[HandlesException(HttpException::class)]
 * public function handleHttpException(HttpException $e): void {}
 */
#[Attribute(Attribute::TARGET_METHOD)]
#[HopWeight(1)]
final readonly class HandlesException
{
    /** @param class-string<Throwable> $exception */
    public function __construct(public string $exception) {}
}
