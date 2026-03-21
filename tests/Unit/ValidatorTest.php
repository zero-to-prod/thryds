<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Validation\Validator;
use ZeroToProd\Thryds\ViewModels\RegisterViewModel;

final class ValidatorTest extends TestCase
{
    private const string valid_name = 'Jane';

    private const string valid_handle = 'janehandle';

    private const string valid_email = 'jane@example.com';

    private const string valid_password = 'securepass';

    private const string short_password = 'short';

    private const string mismatched_password = 'different';

    #[Test]
    public function validInputProducesNoErrors(): void
    {
        $this->assertSame([], Validator::validate(RegisterRequest::from([
            RegisterRequest::name => self::valid_name,
            RegisterRequest::handle => self::valid_handle,
            RegisterRequest::email => self::valid_email,
            RegisterRequest::password => self::valid_password,
            RegisterRequest::password_confirmation => self::valid_password,
        ])));
    }

    #[Test]
    public function missingRequiredFieldsProduceErrors(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            RegisterRequest::name => '',
            RegisterRequest::handle => '',
            RegisterRequest::email => '',
            RegisterRequest::password => '',
            RegisterRequest::password_confirmation => '',
        ]));

        $this->assertSame('Name is required.', $errors[RegisterViewModel::name_error]);
        $this->assertSame('Email is required.', $errors[RegisterViewModel::email_error]);
        $this->assertSame('Password is required.', $errors[RegisterViewModel::password_error]);
        $this->assertSame('Password_confirmation is required.', $errors[RegisterViewModel::password_confirmation_error]);
    }

    #[Test]
    public function invalidEmailProducesError(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            RegisterRequest::name => self::valid_name,
            RegisterRequest::handle => self::valid_handle,
            RegisterRequest::email => 'not-valid',
            RegisterRequest::password => self::valid_password,
            RegisterRequest::password_confirmation => self::valid_password,
        ]));

        $this->assertArrayNotHasKey(RegisterViewModel::name_error, array: $errors);
        $this->assertSame('Enter a valid email address.', $errors[RegisterViewModel::email_error]);
        $this->assertArrayNotHasKey(RegisterViewModel::password_error, array: $errors);
    }

    #[Test]
    public function shortPasswordProducesError(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            RegisterRequest::name => self::valid_name,
            RegisterRequest::handle => self::valid_handle,
            RegisterRequest::email => self::valid_email,
            RegisterRequest::password => self::short_password,
            RegisterRequest::password_confirmation => self::short_password,
        ]));

        $this->assertArrayNotHasKey(RegisterViewModel::name_error, array: $errors);
        $this->assertArrayNotHasKey(RegisterViewModel::email_error, array: $errors);
        $this->assertSame('Password must be at least 8 characters.', $errors[RegisterViewModel::password_error]);
    }

    #[Test]
    public function mismatchedPasswordsProduceError(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            RegisterRequest::name => self::valid_name,
            RegisterRequest::handle => self::valid_handle,
            RegisterRequest::email => self::valid_email,
            RegisterRequest::password => self::valid_password,
            RegisterRequest::password_confirmation => self::mismatched_password,
        ]));

        $this->assertArrayNotHasKey(RegisterViewModel::name_error, array: $errors);
        $this->assertArrayNotHasKey(RegisterViewModel::email_error, array: $errors);
        $this->assertArrayNotHasKey(RegisterViewModel::password_error, array: $errors);
        $this->assertSame('Password does not match.', $errors[RegisterViewModel::password_confirmation_error]);
    }
}
