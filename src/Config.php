<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\DataModel;
use ZeroToProd\Thryds\Helpers\Describe;

/**
 * @method static self from(array{AppEnv?: AppEnv, blade_cache_dir?: string, template_dir?: string} $data)
 */
readonly class Config
{
    use DataModel;

    public const string MAX_REQUESTS = 'MAX_REQUESTS';
    /** @see $APP_ENV */
    public const string APP_ENV = 'APP_ENV';
    #[Describe([Describe::default => APP_ENV::production])]
    public APP_ENV $APP_ENV;

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
        return $this->APP_ENV === APP_ENV::production;
    }
}
