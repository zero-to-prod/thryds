<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares that this view model carries validation errors from a request class.
 *
 * Errors are stored in a single nullable array property rather than
 * individual per-field properties, eliminating manual synchronization
 * between request validation attributes and view model error slots.
 *
 * @template T of object
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[HopWeight(0)]
readonly class HasValidationErrors
{
    /** @param class-string<T> $request */
    public function __construct(public string $request) {}
}
