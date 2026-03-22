<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Validation;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::validation_rules,
    addCase: 'Add enum case. Implement passes() and message() match arms.'
)]
enum Rule: string
{
    case required = 'required';
    case email    = 'email';
    case min      = 'min';
    case max      = 'max';

    public function passes(mixed $value, int|string|null $config): bool
    {
        $string_value = is_string($value) ? $value : '';

        return match ($this) {
            self::required => $string_value !== '',
            self::email    => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            self::min      => strlen(string: $string_value) >= (int) $config,
            self::max      => strlen(string: $string_value) <= (int) $config,
        };
    }

    public function message(string $field, int|string|null $config): string
    {
        return match ($this) {
            self::required => ucfirst(string: $field) . ' is required.',
            self::email    => 'Enter a valid email address.',
            self::min      => ucfirst(string: $field) . " must be at least $config characters.",
            self::max      => ucfirst(string: $field) . " must be at most $config characters.",
        };
    }
}
