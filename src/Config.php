<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\Describe;

/**
 * @method static self from(array{AppEnv?: AppEnv|string, blade_cache_dir?: string, template_dir?: string} $data)
 */
readonly class Config
{
    use DataModel;

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
