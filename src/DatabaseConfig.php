<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

readonly class DatabaseConfig
{
    public string $dsn;

    public function __construct(
        public string $host,
        public int    $port,
        public string $database,
        public string $username,
        public string $password,
    ) {
        $this->dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    }

    public static function fromEnv(): self
    {
        return new self(
            host: (string) getenv(Env::DB_HOST),
            port: (int) (getenv(Env::DB_PORT) ?: 3306),
            database: (string) getenv(Env::DB_DATABASE),
            username: (string) getenv(Env::DB_USERNAME),
            password: (string) getenv(Env::DB_PASSWORD),
        );
    }
}
