<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\Describe;

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

    #[Describe([Describe::default => ''])]
    public string $host;

    #[Describe([Describe::default => 3306])]
    public int $port;

    #[Describe([Describe::default => ''])]
    public string $database;

    #[Describe([Describe::default => ''])]
    public string $username;

    #[Describe([Describe::default => ''])]
    public string $password;

    #[Describe([Describe::cast => [self::class, 'computeDsn'], Describe::default => ''])]
    public string $dsn;

    public static function fromEnv(): self
    {
        return self::from([
            self::host => (string) getenv(Env::DB_HOST),
            self::port => (int) (getenv(Env::DB_PORT) ?: 3306),
            self::database => (string) getenv(Env::DB_DATABASE),
            self::username => (string) getenv(Env::DB_USERNAME),
            self::password => (string) getenv(Env::DB_PASSWORD),
        ]);
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
