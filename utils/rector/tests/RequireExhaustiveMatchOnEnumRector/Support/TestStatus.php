<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireExhaustiveMatchOnEnumRector;

enum TestStatus: string
{
    case active = 'active';
    case inactive = 'inactive';
    case pending = 'pending';
}
