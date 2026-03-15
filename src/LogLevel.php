<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

enum LogLevel: int
{
    case Debug = FRANKENPHP_LOG_LEVEL_DEBUG;
    case Info = FRANKENPHP_LOG_LEVEL_INFO;
    case Warn = FRANKENPHP_LOG_LEVEL_WARN;
    case Error = FRANKENPHP_LOG_LEVEL_ERROR;
}
