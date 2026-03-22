<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Marks a class whose public string constants name keys in a specific context.
 *
 * Rector rules discover these classes via reflection to auto-configure themselves.
 * AI agents read the source and access parameters to understand how the class is used.
 *
 * @example
 * #[KeyRegistry(
 *     Source::http_headers,
 *     addKey: '1. Add constant. 2. Reference via Header::NAME where needed.',
 * )]
 * readonly class Header
 * {
 *     public const string content_type = 'Content-Type';
 * }
 *
 * @example
 * #[KeyRegistry(
 *     Source::server_env,
 *     superglobals: ['_SERVER', '_ENV'],
 *     addKey: '1. Add constant. 2. Add to compose.yaml environment section if needed.',
 * )]
 * readonly class Env
 * {
 *     public const string APP_ENV = 'APP_ENV';
 * }
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
#[HopWeight(0)]
readonly class KeyRegistry
{
    /**
     * @param KeySource  $KeySource     The data source whose keys these constants name.
     * @param string[]   $superglobals  If the source is a superglobal, list the variable names (e.g., ['_SERVER', '_ENV']).
     * @param string     $addKey        Human-readable checklist for what to do when adding a new constant.
     */
    public function __construct(
        public KeySource $KeySource,
        public array $superglobals,
        public string $addKey,
    ) {}
}
