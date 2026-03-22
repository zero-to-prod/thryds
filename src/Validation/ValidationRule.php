<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Validation;

use ZeroToProd\Thryds\Attributes\Infrastructure;

// TODO: [ForbidInterfaceRector] Interfaces define implicit contracts — use PHP attributes to declare properties explicitly. Attributes are discoverable, enforceable, and composable without coupling. See: utils/rector/docs/ForbidInterfaceRector.md
#[Infrastructure]
interface ValidationRule
{
    public function passes(mixed $value, object $context): bool;

    public function message(string $field): string;
}
