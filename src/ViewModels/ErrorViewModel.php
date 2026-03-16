<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Thryds\Helpers\DataModel;

readonly class ErrorViewModel
{
    use DataModel;

    /** @see $message */
    public const string message = 'message';
    public string $message;

    /** @see $status_code */
    public const string status_code = 'status_code';
    public int $status_code;
}
