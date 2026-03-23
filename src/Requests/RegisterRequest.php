<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Requests;

use ZeroToProd\Framework\Attributes\DataModel;
use ZeroToProd\Framework\Attributes\Describe;
use ZeroToProd\Framework\Attributes\Field;
use ZeroToProd\Framework\Attributes\Matches;
use ZeroToProd\Framework\UI\InputType;
use ZeroToProd\Framework\Validation\Rule;
use ZeroToProd\Thryds\Tables\User;

/**
 * Parsed registration form submission.
 *
 * @method static self from(array{name?: ?string, handle?: ?string, email?: ?string, password?: ?string, password_confirmation: string} $data)
 */
readonly class RegisterRequest
{
    use DataModel;

    /** @see $name */
    public const string name = 'name';
    #[Field(
        User::class,
        User::name,
        InputType::text,
        'Name',
        order: 1,
        rules: [],
        optional: false,
    )]
    #[Describe([Describe::nullable => true])]
    public ?string $name;

    /** @see $handle */
    public const string handle = 'handle';
    #[Field(
        User::class,
        User::handle,
        InputType::text,
        'Handle',
        order: 2,
        rules: [],
        optional: false,
    )]
    #[Describe([Describe::nullable => true])]
    public ?string $handle;

    /** @see $email */
    public const string email = 'email';
    #[Field(
        User::class,
        User::email,
        InputType::email,
        'Email',
        order: 3,
        rules: [Rule::required, Rule::email],
        optional: false,
    )]
    #[Describe([Describe::nullable => true])]
    public ?string $email;

    /** @see $password */
    public const string password = 'password';
    #[Field(
        User::class,
        User::password,
        InputType::password,
        'Password',
        order: 4,
        rules: [[Rule::min, 8]],
        optional: false,
    )]
    #[Describe([Describe::nullable => true])]
    public ?string $password;

    /** @see $password_confirmation */
    public const string password_confirmation = 'password_confirmation';
    #[Field(
        null,
        null,
        InputType::password,
        'Confirm Password',
        order: 5,
        rules: [Rule::required],
        optional: false,
    )]
    #[Matches(User::password)]
    public string $password_confirmation;
}
