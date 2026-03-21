<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Jenssegers\Blade\Blade;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Database;

function app(): Container
{
    return Container::getInstance();
}

function blade(): Blade
{
    return app()->make(Blade::class);
}

function config(): Config
{
    return app()->make(Config::class);
}

function db(): Database
{
    return app()->make(Database::class);
}

/**
 * Returns the unqualified (short) class name from a fully qualified class string or object.
 *
 * Example: short_class_name('App\Models\User') returns 'User'
 */
function short_class_name(object|string $class): string
{
    $class = is_object(value: $class) ? $class::class : $class;

    return basename(str_replace('\\', '/', subject: $class));
}
