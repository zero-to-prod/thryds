<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use Zerotoprod\DataModel\Describe;
use ZeroToProd\Thryds\Helpers\DataModel;

readonly class Config
{
    use DataModel;

    /** @see $appEnv */
    public const string appEnv = 'appEnv';
    #[Describe(['default' => 'production'])]
    public string $appEnv;
    /** @see $twigCacheDir */
    public const string twigCacheDir = 'twigCacheDir';
    /** @see $templateDir */
    public const string templateDir = 'templateDir';
    /** @see $isProduction */
    public const string isProduction = 'isProduction';

    #[Describe(['default' => '/app/var/cache/twig'])]
    public string $twigCacheDir;

    #[Describe(['default' => '/app/templates'])]
    public string $templateDir;

    #[Describe(['cast' => [self::class, 'isProduction']])]
    public bool $isProduction;

    public static function isProduction(mixed $value, array $context): bool
    {
        return ($context['appEnv'] ?? 'production') === 'production';
    }
}
