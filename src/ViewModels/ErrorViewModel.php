<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\ViewModel;

/**
 * @method static self from(array{message: string, status_code: int} $data)
 */
#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;
    public const string view_key = 'ErrorViewModel';

    /** @see $message */
    public const string message = 'message';
    public string $message;

    /** @see $status_code */
    public const string status_code = 'status_code';
    public int $status_code;
}
