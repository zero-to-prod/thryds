<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Requests;

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\Input;
use ZeroToProd\Thryds\Attributes\Validate;
use ZeroToProd\Thryds\Tables\UserColumns;
use ZeroToProd\Thryds\UI\InputType;
use ZeroToProd\Thryds\Validation\Rule;

/**
 * Parsed registration form submission.
 *
 * @method static self from(array{name: ?string, handle: ?string, email: ?string, password: ?string, password_confirmation: string} $data)
 */
readonly class RegisterRequest
{
    use DataModel;
    use UserColumns;

    #[Input(
        InputType::text,
        'Name'
    )]
    #[Validate(Rule::required)]
    public ?string $name;

    #[Input(
        InputType::text,
        'Handle'
    )]
    #[Validate(Rule::required)]
    public ?string $handle;

    #[Input(
        InputType::email,
        'Email'
    )]
    #[Validate(
        Rule::required,
        Rule::email
    )]
    public ?string $email;

    #[Input(
        InputType::password,
        'Password'
    )]
    #[Validate(
        Rule::required,
        [Rule::min, 8]
    )]
    public ?string $password;

    /** @see $password_confirmation */
    public const string password_confirmation = 'password_confirmation';
    #[Input(
        InputType::password,
        'Confirm Password'
    )]
    #[Validate(
        Rule::required,
        [Rule::matches, 'password']
    )]
    public string $password_confirmation;
}
