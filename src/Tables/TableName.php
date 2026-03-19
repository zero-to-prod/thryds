<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tables;

enum TableName: string
{
    case migrations = 'migrations';
    case users = 'users';
}
