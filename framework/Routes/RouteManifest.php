<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes;

use ZeroToProd\Framework\Attributes\KeyRegistry;
use ZeroToProd\Framework\Attributes\KeySource;

/**
 * String key constants for the route manifest JSON returned by the /_routes endpoint.
 *
 * Top-level entry shape:
 *   { "name": "login", "path": "/login", "description": "User authentication",
 *     "operations": [{ "method": "GET", "description": "Render login form" }, ...] }
 */
#[KeyRegistry(
    KeySource::route_manifest,
    superglobals: [],
    addKey: '1. Add constant. 2. Add the corresponding field in RouteRegistrar::register() manifest map.',
)]
readonly class RouteManifest
{
    // Route-level fields
    public const string name        = 'name';
    public const string path        = 'path';
    public const string description = 'description';
    public const string operations  = 'operations';

    // Operation-level fields (inside each operations entry)
    public const string method   = 'method';
    public const string strategy = 'strategy';
}
