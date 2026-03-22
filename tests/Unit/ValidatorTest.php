<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Validation\Validator;

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

        $this->assertSame('Name is required.', $errors[Validator::errorKey(RegisterRequest::name)]);
        $this->assertSame('Email is required.', $errors[Validator::errorKey(RegisterRequest::email)]);
        $this->assertSame('Password is required.', $errors[Validator::errorKey(RegisterRequest::password)]);
        $this->assertSame('Password_confirmation is required.', $errors[Validator::errorKey(RegisterRequest::password_confirmation)]);
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

        $this->assertArrayNotHasKey(Validator::errorKey(RegisterRequest::name), array: $errors);
        $this->assertSame('Enter a valid email address.', $errors[Validator::errorKey(RegisterRequest::email)]);
        $this->assertArrayNotHasKey(Validator::errorKey(RegisterRequest::password), array: $errors);
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

        $this->assertArrayNotHasKey(Validator::errorKey(RegisterRequest::name), array: $errors);
        $this->assertArrayNotHasKey(Validator::errorKey(RegisterRequest::email), array: $errors);
        $this->assertSame('Password must be at least 8 characters.', $errors[Validator::errorKey(RegisterRequest::password)]);
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

        $this->assertArrayNotHasKey(Validator::errorKey(RegisterRequest::name), array: $errors);
        $this->assertArrayNotHasKey(Validator::errorKey(RegisterRequest::email), array: $errors);
        $this->assertArrayNotHasKey(Validator::errorKey(RegisterRequest::password), array: $errors);
        $this->assertSame('Password does not match.', $errors[Validator::errorKey(RegisterRequest::password_confirmation)]);
    }
}
