<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\ViewModel;

/**
 * Form state for the registration view — repopulated fields and per-field validation errors.
 *
 * @method static self from(array{name: string, email: string, name_error: string, email_error: string, password_error: string, password_confirmation_error: string} $data)
 */
#[ViewModel]
readonly class RegisterViewModel
{
    use DataModel;
    public const string view_key = 'RegisterViewModel';

    /** @see $name */
    public const string name = 'name';
    public string $name;

    /** @see $email */
    public const string email = 'email';
    public string $email;

    /** @see $name_error */
    public const string name_error = 'name_error';
    public string $name_error;

    /** @see $email_error */
    public const string email_error = 'email_error';
    public string $email_error;

    /** @see $password_error */
    public const string password_error = 'password_error';
    public string $password_error;

    /** @see $password_confirmation_error */
    public const string password_confirmation_error = 'password_confirmation_error';
    public string $password_confirmation_error;
}
