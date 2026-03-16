<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\DataModel;
use ZeroToProd\Thryds\Helpers\Describe;

readonly class Config
{
    use DataModel;

    public const string APP_ENV = 'APP_ENV';
    /** @see $AppEnv */
    public const string AppEnv = 'AppEnv';
    #[Describe([Describe::default => AppEnv::production])]
    public AppEnv $AppEnv;

    /** @see $blade_cache_dir */
    public const string blade_cache_dir = 'blade_cache_dir';
    /** @see $template_dir */
    public const string template_dir = 'template_dir';

    #[Describe([Describe::default => '/app/var/cache/blade'])]
    public string $blade_cache_dir;

    #[Describe([Describe::default => '/app/templates'])]
    public string $template_dir;

    public function isProduction(): bool
    {
        return $this->AppEnv === AppEnv::production;
    }
}
