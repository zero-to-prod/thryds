<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\KeyRegistry;
use ZeroToProd\Thryds\Attributes\KeySource;

/** @see opcache_get_status() */
#[KeyRegistry(
    KeySource::opcache_get_status,
)]
readonly class OpcacheStatus
{
    // Top-level keys
    public const string scripts = 'scripts';
    public const string opcache_statistics = 'opcache_statistics';
    public const string memory_usage = 'memory_usage';
    public const string preload_statistics = 'preload_statistics';

    // opcache_statistics sub-keys
    public const string hits = 'hits';
    public const string misses = 'misses';
    public const string num_cached_scripts = 'num_cached_scripts';

    // memory_usage sub-keys
    public const string used_memory = 'used_memory';
    public const string free_memory = 'free_memory';
    public const string wasted_memory = 'wasted_memory';
}
