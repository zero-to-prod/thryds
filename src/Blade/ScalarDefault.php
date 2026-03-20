<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

/** Maps PHP scalar type names to their zero-value defaults. */
#[ClosedSet(
    Domain::scalar_defaults,
    addCase: 'Add enum case matching PHP type name, then add its zero-value to zeroValue().',
)]
enum ScalarDefault: string
{
    case string = 'string';
    case int = 'int';
    case float = 'float';
    case bool = 'bool';
    case array = 'array';

    /** @return string|int|float|bool|array<empty, empty> */
    public function zeroValue(): string|int|float|bool|array
    {
        return match ($this) {
            self::string => '',
            self::int => 0,
            self::float => 0.0,
            self::bool => false,
            self::array => [],
        };
    }
}
