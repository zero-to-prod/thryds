<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Marks a class whose public string constants name keys in a specific context.
 *
 * Rector rules discover these classes via reflection to auto-configure themselves.
 * AI agents read the source and access parameters to understand how the class is used.
 *
 * @example
 * #[KeyRegistry(
 *     source: 'HTTP headers',
 *     used_in: [[MessageInterface::class, 'getHeaderLine']],
 * )]
 * readonly class Header
 * {
 *     public const string content_type = 'Content-Type';
 * }
 *
 * @example
 * #[KeyRegistry(
 *     source: '$_SERVER / $_ENV',
 *     superglobals: ['_SERVER', '_ENV'],
 * )]
 * readonly class Env
 * {
 *     public const string APP_ENV = 'APP_ENV';
 * }
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class KeyRegistry
{
    /**
     * @param string                             $source        Human-readable name of the data source whose keys these constants name.
     * @param list<array{class: class-string, method: string}>  $used_in       Where these keys are consumed. Each entry is [Class::class, 'method'] — AST-refactorable.
     * @param string[]                           $superglobals  If the source is a superglobal, list the variable names (e.g., ['_SERVER', '_ENV']).
     */
    public function __construct(
        public string $source,
        public array $used_in = [],
        public array $superglobals = [],
    ) {}
}
