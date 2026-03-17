<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

/**
 * Marks a class whose public string constants name keys in a specific context.
 *
 * Rector rules discover these classes via reflection to auto-configure themselves.
 * AI agents read the source and access parameters to understand how the class is used.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class NamesKeys
{
    /**
     * @param string   $source       Human-readable name of the data source whose keys these constants name.
     * @param string   $access       Example usage pattern showing how to access a key using the constant.
     * @param string[] $superglobals If the source is a superglobal, list the variable names (e.g., ['_SERVER', '_ENV']).
     */
    public function __construct(
        public string $source,
        public string $access = '',
        public array $superglobals = [],
    ) {}
}
