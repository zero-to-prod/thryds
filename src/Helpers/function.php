<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

/**
 * Returns the unqualified (short) class name from a fully qualified class string or object.
 *
 * Example: short_class_name('App\Models\User') returns 'User'
 */
function short_class_name(object|string $class): string
{
    $class = is_object($class) ? $class::class : $class;

    return basename(str_replace('\\', '/', $class));
}
