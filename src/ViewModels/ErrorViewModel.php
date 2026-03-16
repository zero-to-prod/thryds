<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Thryds\Helpers\DataModel;

/**
 * @method static self from(array{message: string, status_code: int} $data)
 */
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
