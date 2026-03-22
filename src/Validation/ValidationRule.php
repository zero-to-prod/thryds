<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Validation;

use ZeroToProd\Thryds\Attributes\Infrastructure;

#[Infrastructure]
interface ValidationRule
{
    public function passes(mixed $value, object $context): bool;

    public function message(string $field): string;
}
