<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ForbidStringArgForEnumParamRector;

enum TestAppEnv: string
{
    case production = 'production';
    case development = 'development';
}
