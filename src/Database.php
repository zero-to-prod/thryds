<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use Closure;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

class Database
{
    private ?PDO $PDO = null;
    private readonly DatabaseConfig $DatabaseConfig;

    public function __construct(DatabaseConfig $DatabaseConfig)
    {
        $this->DatabaseConfig = $DatabaseConfig;
    }

    /**
     * SELECT — returns all rows as associative arrays.
     *
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * SELECT — returns one row or null.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function one(string $sql, array $params = []): ?array
    {
        /** @var array<string, mixed>|false $row */
        $row = $this->run($sql, $params)->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * SELECT — returns a single scalar value or null.
     *
     * @param array<string, mixed> $params
     */
    public function scalar(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * INSERT / UPDATE / DELETE — returns affected row count.
     *
     * @param array<string, mixed> $params
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    /**
     * INSERT — returns last insert ID.
     *
     * @param array<string, mixed> $params
     */
    public function insert(string $sql, array $params = []): string
    {
        $this->run($sql, $params);

        return (string) $this->pdo()->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->pdo()->beginTransaction();
    }

    public function rollBack(): void
    {
        $this->pdo()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->PDO !== null && $this->PDO->inTransaction();
    }

    /** Wrap a block in a transaction; re-throws on failure.
     *
     * @throws Throwable
     */
    public function transaction(Closure $Closure): mixed
    {
        $this->pdo()->beginTransaction();
        try {
            $result = $Closure($this);
            $this->pdo()->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo()->rollBack();
            throw $e;
        }
    }

    private function pdo(): PDO
    {
        return $this->PDO ??= self::connect($this->DatabaseConfig);
    }

    /** @param array<string, mixed> $params */
    private function run(string $sql, array $params): PDOStatement
    {
        try {
            $stmt = $this->pdo()->prepare(query: $sql);
            $stmt->execute($params);

            return $stmt;
        } catch (PDOException $e) {
            if (self::isGoneAway(PDOException: $e)) {
                $this->PDO = self::connect($this->DatabaseConfig);
                $stmt = $this->PDO->prepare(query: $sql);
                $stmt->execute($params);

                return $stmt;
            }
            throw $e;
        }
    }

    private static function connect(DatabaseConfig $DatabaseConfig): PDO
    {
        $PDO = new PDO(
            dsn: $DatabaseConfig->dsn,
            username: $DatabaseConfig->username,
            password: $DatabaseConfig->password,
            options: [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ],
        );
        $PDO->exec("SET time_zone = '+00:00'");

        return $PDO;
    }

    private static function isGoneAway(PDOException $PDOException): bool
    {
        return str_contains($PDOException->getMessage(), 'server has gone away')
            || str_contains($PDOException->getMessage(), 'Lost connection');
    }
}
