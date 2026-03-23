<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Framework\Attributes\DataModel;
use ZeroToProd\Framework\Attributes\Describe;
use ZeroToProd\Framework\Attributes\HasValidationErrors;
use ZeroToProd\Framework\Attributes\StubValue;
use ZeroToProd\Framework\Attributes\ViewModel;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Tables\UserColumns;

/**
 * Form state for the registration view — repopulated fields and per-field validation errors.
 *
 * @method static self from(array{errors?: ?array<string, string>} $data)
 */
#[ViewModel(key: 'RegisterViewModel')]
#[HasValidationErrors(RegisterRequest::class)]
readonly class RegisterViewModel
{
    use DataModel;
    use UserColumns;

    public const string view_key = 'RegisterViewModel';

    /** @see $errors */
    public const string errors = 'errors';
    /** @var ?array<string, string> */
    #[Describe([
        Describe::nullable => true,
    ])]
    #[StubValue(null)]
    public ?array $errors;
}
