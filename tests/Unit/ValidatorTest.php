<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Validation\Validator;

final class ValidatorTest extends TestCase
{
    #[Test]
    public function validInputProducesNoErrors(): void
    {
        $this->assertSame([], Validator::validate(RegisterRequest::from([
            RegisterRequest::name => 'Jane',
            RegisterRequest::handle => 'janehandle',
            RegisterRequest::email => 'jane@example.com',
            RegisterRequest::password => 'securepass',
            RegisterRequest::password_confirmation => 'securepass',
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

        $this->assertSame('Name is required.', $errors['name_error']);
        $this->assertSame('Email is required.', $errors['email_error']);
        $this->assertSame('Password is required.', $errors['password_error']);
        $this->assertSame('Password_confirmation is required.', $errors['password_confirmation_error']);
    }

    #[Test]
    public function invalidEmailProducesError(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            RegisterRequest::name => 'Jane',
            RegisterRequest::handle => 'janehandle',
            RegisterRequest::email => 'not-valid',
            RegisterRequest::password => 'securepass',
            RegisterRequest::password_confirmation => 'securepass',
        ]));

        $this->assertArrayNotHasKey('name_error', array: $errors);
        $this->assertSame('Enter a valid email address.', $errors['email_error']);
        $this->assertArrayNotHasKey('password_error', array: $errors);
    }

    #[Test]
    public function shortPasswordProducesError(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            RegisterRequest::name => 'Jane',
            RegisterRequest::handle => 'janehandle',
            RegisterRequest::email => 'jane@example.com',
            RegisterRequest::password => 'short',
            RegisterRequest::password_confirmation => 'short',
        ]));

        $this->assertArrayNotHasKey('name_error', array: $errors);
        $this->assertArrayNotHasKey('email_error', array: $errors);
        $this->assertSame('Password must be at least 8 characters.', $errors['password_error']);
    }

    #[Test]
    public function mismatchedPasswordsProduceError(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            RegisterRequest::name => 'Jane',
            RegisterRequest::handle => 'janehandle',
            RegisterRequest::email => 'jane@example.com',
            RegisterRequest::password => 'securepass',
            RegisterRequest::password_confirmation => 'different',
        ]));

        $this->assertArrayNotHasKey('name_error', array: $errors);
        $this->assertArrayNotHasKey('email_error', array: $errors);
        $this->assertArrayNotHasKey('password_error', array: $errors);
        $this->assertSame('Password does not match.', $errors['password_confirmation_error']);
    }
}
