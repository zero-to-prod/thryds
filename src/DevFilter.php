<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

/**
 * Shared path filters for identifying dev-only and non-production scripts.
 *
 * Used by generate-preload.php (to exclude from preload) and
 * opcache-audit.php (to count expected non-preloaded scripts).
 */
readonly class DevFilter
{
    /** @var list<string> Vendor paths that are dev-only (not needed at runtime). */
    public const array dev_vendors = [
        '/vendor/phpunit/',
        '/vendor/phpstan/',
        '/vendor/rector/',
        '/vendor/friendsofphp/',
        '/vendor/myclabs/',
        '/vendor/sebastian/',
        '/vendor/theseer/',
        '/vendor/nikic/php-parser/',
    ];

    /** @var list<string> Directory segments that indicate non-production scripts. */
    public const array excluded_dirs = [
        '/var/cache/',
        '/tests/',
        '/utils/',
    ];

    public static function isDevPath(string $path): bool
    {
        foreach (self::dev_vendors as $vendor) {
            if (str_contains(haystack: $path, needle: $vendor)) {
                return true;
            }
        }

        foreach (self::excluded_dirs as $dir) {
            if (str_contains(haystack: $path, needle: $dir)) {
                return true;
            }
        }

        return false;
    }
}
