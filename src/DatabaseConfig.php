<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ReflectionClass;
use ReflectionProperty;
use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\Describe;
use ZeroToProd\Thryds\Attributes\EnvVar;

/**
 * @method static self from(array{host?: string, port?: int, database?: string, username?: string, password?: string, dsn?: string} $data)
 */
readonly class DatabaseConfig
{
    use DataModel;

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

    #[EnvVar(Env::DB_HOST)]
    #[Describe([Describe::default => ''])]
    public string $host;

    #[EnvVar(Env::DB_PORT)]
    #[Describe([Describe::default => 3306])]
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

    #[Describe([Describe::cast => [self::class, 'computeDsn'], Describe::default => ''])]
    public string $dsn;

    public static function fromEnv(): self
    {
        return self::fromEnvData(getenv());
    }

    /**
     * Builds configuration from an environment array using #[EnvVar] attribute declarations.
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

        /** @phpstan-ignore argument.type (DataModel coerces scalar types from env strings) */
        return self::from($data);
    }

    /**
     * @param array<string, string|int> $context
     */
    public static function computeDsn(mixed $value, array $context): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            (string) ($context[self::host] ?? ''),
            (int) ($context[self::port] ?? 3306),
            (string) ($context[self::database] ?? ''),
        );
    }
}
