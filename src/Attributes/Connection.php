<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use function app;

use Attribute;
use ReflectionClass;
use ZeroToProd\Thryds\Database;

/**
 * Declares which database connection a table uses.
 *
 * Placed on Table classes so the connection is a single source of truth.
 * Query traits resolve the connection by following: query → table attribute → #[Connection].
 *
 * @example
 * #[Connection(database: Database::class)]
 * #[Table(TableName: TableName::users, ...)]
 * readonly class User { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[HopWeight(0)]
readonly class Connection
{
    /** @param class-string $database Container-resolvable class for the connection. */
    public function __construct(
        public string $database,
    ) {}

    /**
     * Resolves the database connection for a table class from its #[Connection] attribute.
     *
     * Falls back to Database::class when no attribute is present.
     *
     * @param class-string $class The table class to resolve the connection for.
     */
    public static function resolve(string $class): Database
    {
        $attrs = new ReflectionClass(objectOrClass: $class)->getAttributes(self::class);

        /** @var Database */
        return app()->make(
            $attrs !== [] ? $attrs[0]->newInstance()->database : Database::class
        );
    }
}
