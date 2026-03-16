<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

readonly class OpcacheStatusRoute
{
    public const string pattern = '/_opcache/status';
    public const string scripts_pattern = '/_opcache/scripts';
    public const string scripts = 'scripts';
}
