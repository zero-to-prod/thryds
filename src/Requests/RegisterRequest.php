<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Requests;

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\Input;
use ZeroToProd\Thryds\Attributes\Validates;
use ZeroToProd\Thryds\Tables\User;
use ZeroToProd\Thryds\Tables\UserColumns;
use ZeroToProd\Thryds\UI\InputType;
use ZeroToProd\Thryds\Validation\Rule;

/**
 * Parsed registration form submission.
 *
 * @method static self from(array{password_confirmation: string} $data)
 */
#[Validates(
    User::name,
    Rule::required
)]
#[Validates(
    User::handle,
    Rule::required
)]
#[Validates(
    User::email,
    Rule::required,
    Rule::email
)]
#[Validates(
    User::password,
    Rule::required,
    [Rule::min, 8]
)]
#[Validates(
    'password_confirmation',
    Rule::required,
    [Rule::matches, User::password]
)]
readonly class RegisterRequest
{
    use DataModel;
    use UserColumns;

    /** @see $password_confirmation */
    public const string password_confirmation = 'password_confirmation';
    #[Input(
        InputType::password,
        'Confirm Password'
    )]
    public string $password_confirmation;
}
