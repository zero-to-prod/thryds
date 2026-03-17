<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\LimitsChoices;

/**
 * Backed by constants defined by ext-frankenphp.
 * Values: Debug=0, Info=1, Warn=2, Error=3.
 * @see https://frankenphp.dev/docs/worker/#logging
 */
#[LimitsChoices]
enum LogLevel: int
{
    case Debug = FRANKENPHP_LOG_LEVEL_DEBUG;
    case Info = FRANKENPHP_LOG_LEVEL_INFO;
    case Warn = FRANKENPHP_LOG_LEVEL_WARN;
    case Error = FRANKENPHP_LOG_LEVEL_ERROR;
}
