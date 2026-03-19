<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ForbidBareGetenvCallRector;

class Env
{
    public const string DB_HOST = 'DB_HOST';
    public const string DB_PORT = 'DB_PORT';
    public const string DB_DATABASE = 'DB_DATABASE';
    public const string DB_USERNAME = 'DB_USERNAME';
    public const string DB_PASSWORD = 'DB_PASSWORD';
}
