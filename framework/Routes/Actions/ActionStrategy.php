<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes\Actions;

use Attribute;
use ZeroToProd\Framework\Attributes\Infrastructure;

/**
 * Marks a class as a route action strategy.
 *
 * Classes carrying this attribute must declare toCallable(BackedEnum $BackedEnum, HttpMethod $HttpMethod): callable.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[Infrastructure]
readonly class ActionStrategy {}
