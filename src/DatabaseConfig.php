<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ReflectionClass;
use ReflectionProperty;
use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\Describe;
use ZeroToProd\Thryds\Attributes\EnvVar;
use ZeroToProd\Thryds\Schema\Driver;

/**
 * @method static self from(array{Driver?: Driver|string, host?: string, port?: int, database?: string, username?: string, password?: string, dsn?: string} $data)
 */
readonly class DatabaseConfig
{
    use DataModel;

    /** @see $Driver */
    public const string Driver = 'Driver';
    /** @see $host */
    public const string host = 'host';
    /** @see $port */
    public const string port = 'port';
    /** @see $database */
    public const string database = 'database';
    /** @see $username */
    public const string username = 'username';
    /** @see $password */
    public const string password = 'password';
    /** @see $dsn */
    public const string dsn = 'dsn';

    #[EnvVar(Env::DB_DRIVER)]
    #[Describe([Describe::cast => [self::class, 'castDriver'], Describe::default => Driver::mysql])]
    public Driver $Driver;

    #[EnvVar(Env::DB_HOST)]
    #[Describe([Describe::default => ''])]
    public string $host;

    #[EnvVar(Env::DB_PORT)]
    #[Describe([Describe::cast => [self::class, 'computePort'], Describe::default => 0])]
    public int $port;

    #[EnvVar(Env::DB_DATABASE)]
    #[Describe([Describe::default => ''])]
    public string $database;

    #[EnvVar(Env::DB_USERNAME)]
    #[Describe([Describe::default => ''])]
    public string $username;

    #[EnvVar(Env::DB_PASSWORD)]
    #[Describe([Describe::default => ''])]
    public string $password;

    #[Describe([Describe::default => [self::class, 'computeDsn']])]
    public string $dsn;

    public static function fromEnv(): self
    {
        return self::fromEnvData(getenv());
    }

    /**
     * Builds configuration from an environment array using environment variable declaration attributes.
     *
     * @param array<string, string> $env
     */
    public static function fromEnvData(array $env): self
    {
        $data = [];
        foreach (new ReflectionClass(static::class)->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attrs = $property->getAttributes(EnvVar::class);
            if ($attrs === []) {
                continue;
            }
            $key = $attrs[0]->newInstance()->key;
            if (isset($env[$key])) {
                $data[$property->getName()] = $env[$key];
            }
        }

        /** @var array{Driver?: string, host?: string, port?: int, database?: string, username?: string, password?: string} $data */
        return self::from($data);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function castDriver(mixed $value, array $context): Driver
    {
        return self::resolveDriver($value);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function computePort(mixed $value, array $context): int
    {
        /** @var int|string $value */
        $port = (int) $value;
        if ($port > 0) {
            return $port;
        }

        return self::resolveDriver($context[self::Driver] ?? Driver::mysql)->defaultPort();
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function computeDsn(mixed $value, array $context): string
    {
        $Driver = self::resolveDriver($context[self::Driver] ?? Driver::mysql);

        /** @var string $host */
        $host = $context[self::host] ?? '';
        $port = $context[self::port] ?? null;

        $database = $context[self::database] ?? '';

        return $Driver->dsn($host, is_numeric(value: $port) ? (int) $port : $Driver->defaultPort(), is_string(value: $database) ? $database : '');
    }

    private static function resolveDriver(mixed $value): Driver
    {
        if ($value instanceof Driver) {
            return $value;
        }

        return Driver::tryFrom(is_string($value) ? $value : '') ?? Driver::mysql;
    }
}
