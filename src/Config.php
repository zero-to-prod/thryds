<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\DataModel;
use ZeroToProd\Thryds\Helpers\Describe;

readonly class Config
{
    use DataModel;

    public const string APP_ENV = 'APP_ENV';
    public const string TWIG_CACHE = 'cache';
    public const string TWIG_AUTO_RELOAD = 'auto_reload';

    /** @see $appEnv */
    public const string appEnv = 'appEnv';
    #[Describe([Describe::default => AppEnv::Production])]
    public AppEnv $appEnv;

    /** @see $twigCacheDir */
    public const string twigCacheDir = 'twigCacheDir';
    /** @see $templateDir */
    public const string templateDir = 'templateDir';
    /** @see $isProduction */
    public const string isProduction = 'isProduction';

    #[Describe([Describe::default => '/app/var/cache/twig'])]
    public string $twigCacheDir;

    #[Describe([Describe::default => '/app/templates'])]
    public string $templateDir;

    #[Describe([Describe::cast => [self::class, 'isProduction']])]
    public bool $isProduction;

    public static function isProduction(mixed $value, array $context): bool
    {
        return ($context[self::appEnv] ?? AppEnv::Production->value) === AppEnv::Production->value;
    }
}
