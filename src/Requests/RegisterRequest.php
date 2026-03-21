<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Requests;

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\Validate;
use ZeroToProd\Thryds\Tables\UserColumns;
use ZeroToProd\Thryds\Validation\Rule;

/**
 * Parsed registration form submission.
 *
 * @method static self from(array{name: string, email: string, password: string, password_confirmation: string} $data)
 */
readonly class RegisterRequest
{
    use DataModel;
    use UserColumns;

    #[Validate(Rule::required)]
    public ?string $name;

    #[Validate(
        Rule::required,
        Rule::email
    )]
    public ?string $email;

    #[Validate(
        Rule::required,
        [Rule::min, 8]
    )]
    public ?string $password;

    /** @see $password_confirmation */
    public const string password_confirmation = 'password_confirmation';
    #[Validate(
        Rule::required,
        [Rule::matches, 'password']
    )]
    public string $password_confirmation;
}
