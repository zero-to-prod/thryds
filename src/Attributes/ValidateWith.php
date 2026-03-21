<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Validation\ValidationRule;

/** @template T of ValidationRule */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
readonly class ValidateWith
{
    /** @param class-string<T> $rule */
    public function __construct(public string $rule) {}
}
