<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\StubValue;
use ZeroToProd\Thryds\Attributes\ViewModel;

/**
 * HTTP error context (message, status_code) passed to the error view.
 *
 * @method static self from(array{message: string, status_code: int} $data)
 */
#[ViewModel(key: 'ErrorViewModel')]
readonly class ErrorViewModel
{
    use DataModel;
    public const string view_key = 'ErrorViewModel';

    /** @see $message */
    public const string message = 'message';
    #[StubValue('')]
    public string $message;

    /** @see $status_code */
    public const string status_code = 'status_code';
    #[StubValue(0)]
    public int $status_code;
}
