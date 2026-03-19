<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\KeyRegistry;
use ZeroToProd\Thryds\Attributes\KeySource;

#[KeyRegistry(
    KeySource::log_context_array,
    superglobals: [],
    addKey: '1. Add constant. 2. Pass in context array via Log::method([LogContext::KEY => $value]).'
)]
readonly class LogContext
{
    public const string event = 'event';
    public const string exception = 'exception';
    public const string file = 'file';
    public const string line = 'line';
    public const string request_id = 'request_id';
    public const string unhandled_exception = 'unhandled_exception';
}
