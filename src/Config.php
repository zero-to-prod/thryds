<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\Describe;

/**
 * @method static self from(array{AppEnv?: AppEnv|string, blade_cache_dir?: string, template_dir?: string, DatabaseConfig?: DatabaseConfig} $data)
 */
readonly class Config
{
    use DataModel;

    #[Describe([Describe::default => AppEnv::production])]
    public AppEnv $AppEnv;

    #[Describe([Describe::default => '/app/var/cache/blade'])]
    public string $blade_cache_dir;

    #[Describe([Describe::default => '/app/templates'])]
    public string $template_dir;

    #[Describe([Describe::cast => [DatabaseConfig::class, 'from'], Describe::default => [self::class, 'defaultDatabaseConfig']])]
    public DatabaseConfig $DatabaseConfig;

    public static function defaultDatabaseConfig(): DatabaseConfig
    {
        return DatabaseConfig::from([]);
    }

    public function isProduction(): bool
    {
        return $this->AppEnv === AppEnv::production;
    }

    public static function fromEnv(string $base_dir): self
    {
        return self::from([
            ConfigKey::AppEnv->value => AppEnv::fromEnv(),
            ConfigKey::blade_cache_dir->value => $base_dir . '/var/cache/blade',
            ConfigKey::template_dir->value => $base_dir . '/templates',
            ConfigKey::DatabaseConfig->value => DatabaseConfig::fromEnv(),
        ]);
    }
}
