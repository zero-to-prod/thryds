<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\DataModel;
use ZeroToProd\Thryds\Helpers\Describe;

readonly class Config
{
    use DataModel;

    public const string APP_ENV = 'APP_ENV';
    /** @see $appEnv */
    public const string appEnv = 'appEnv';
    #[Describe([Describe::default => AppEnv::Production])]
    public AppEnv $appEnv;

    /** @see $bladeCacheDir */
    public const string bladeCacheDir = 'bladeCacheDir';
    /** @see $templateDir */
    public const string templateDir = 'templateDir';

    #[Describe([Describe::default => '/app/var/cache/blade'])]
    public string $bladeCacheDir;

    #[Describe([Describe::default => '/app/templates'])]
    public string $templateDir;

    public function isProduction(): bool
    {
        return $this->appEnv === AppEnv::Production;
    }
}
