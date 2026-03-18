<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

/**
 * Backed by constants defined by ext-frankenphp.
 * Values: Debug=0, Info=1, Warn=2, Error=3.
 * @see https://frankenphp.dev/docs/worker/#logging
 */
#[ClosedSet(Domain::log_severity_levels, addCase: 'Add enum case. No other changes needed — Log methods accept any LogLevel.')]
enum LogLevel: int
{
    case Debug = FRANKENPHP_LOG_LEVEL_DEBUG;
    case Info = FRANKENPHP_LOG_LEVEL_INFO;
    case Warn = FRANKENPHP_LOG_LEVEL_WARN;
    case Error = FRANKENPHP_LOG_LEVEL_ERROR;
}
