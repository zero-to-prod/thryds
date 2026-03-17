<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

/**
 * Shared filter for identifying dev-only and non-production scripts.
 *
 * Used by generate-preload.php (to exclude from preload) and
 * opcache-audit.php (to count expected non-preloaded scripts).
 */
readonly class DevFilter
{
    public static function isDevPath(string $path): bool
    {
        return array_any(DevPath::cases(), fn($devPath): bool => str_contains(haystack: $path, needle: $devPath->value));
    }
}
