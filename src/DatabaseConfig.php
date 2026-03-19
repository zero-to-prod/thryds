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
            host: (string) getenv('DB_HOST'),
            port: (int) (getenv('DB_PORT') ?: 3306),
            database: (string) getenv('DB_DATABASE'),
            username: (string) getenv('DB_USERNAME'),
            password: (string) getenv('DB_PASSWORD'),
        );
    }
}
