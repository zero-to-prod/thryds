<?php

declare(strict_types=1);

namespace ZeroToProd\Framework;

use RuntimeException;
use ZeroToProd\Framework\Attributes\Infrastructure;

/**
 * Narrows mixed database row values to expected scalar types.
 */
#[Infrastructure]
trait RowAccess
{
    /**
     * Reads a string value from a database row, asserting the type.
     *
     * @param array<string, mixed> $row
     */
    private function rowStr(array $row, string $key): string
    {
        $value = $row[$key];
        if (!is_string($value)) {
            throw new RuntimeException("Expected string for key '$key', got " . gettype($value) . '.'); // @codeCoverageIgnore
        }

        return $value;
    }
}
