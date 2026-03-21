<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Validation\Validator;

// TODO: [DetectParallelBladePhpBehaviorRector] Use ZeroToProd\Thryds\Requests\RegisterRequest::password_confirmation instead of hardcoded 'password_confirmation'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md
// TODO: [DetectParallelBladePhpBehaviorRector] Use ZeroToProd\Thryds\Validation\Rule::email instead of hardcoded 'email'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md
// TODO: [ForbidCrossFileStringDuplicationRector] string 'Enter a valid email address.' appears in 3 files. Extract to a shared constant. See: utils/rector/docs/ForbidCrossFileStringDuplicationRector.md
// TODO: [ForbidCrossFileStringDuplicationRector] string 'password' appears in 3 files. Extract to a shared constant. See: utils/rector/docs/ForbidCrossFileStringDuplicationRector.md
// TODO: [ForbidCrossFileStringDuplicationRector] string 'email' appears in 3 files. Extract to a shared constant. See: utils/rector/docs/ForbidCrossFileStringDuplicationRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'short' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'password_confirmation_error' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'password_error' (used 4x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'email_error' (used 4x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'name_error' (used 4x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'password_confirmation' (used 5x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'securepass' (used 5x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'password' (used 5x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'jane@example.com' (used 3x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'email' (used 5x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'Jane' (used 4x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'name' (used 5x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
final class ValidatorTest extends TestCase
{
    #[Test]
    public function validInputProducesNoErrors(): void
    {
        $this->assertSame([], Validator::validate(RegisterRequest::from([
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'Jane' is a value of RegisterRequest::$name. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::name => 'Jane',
            RegisterRequest::email => 'jane@example.com',
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'securepass' is a value of RegisterRequest::$password. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::password => 'securepass',
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'securepass' is a value of RegisterRequest::$password_confirmation. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::password_confirmation => 'securepass',
        ])));
    }

    #[Test]
    public function missingRequiredFieldsProduceErrors(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. '' is a value of RegisterRequest::$name. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::name => '',
            RegisterRequest::email => '',
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. '' is a value of RegisterRequest::$password. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::password => '',
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. '' is a value of RegisterRequest::$password_confirmation. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::password_confirmation => '',
        ]));

        // TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value 'name_error' on the appropriate class. See: utils/rector/docs/ForbidMagicStringArrayKeyRector.md
        $this->assertSame('Name is required.', $errors['name_error']);
        // TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value 'email_error' on the appropriate class. See: utils/rector/docs/ForbidMagicStringArrayKeyRector.md
        $this->assertSame('Email is required.', $errors['email_error']);
        // TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value 'password_error' on the appropriate class. See: utils/rector/docs/ForbidMagicStringArrayKeyRector.md
        $this->assertSame('Password is required.', $errors['password_error']);
        // TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value 'password_confirmation_error' on the appropriate class. See: utils/rector/docs/ForbidMagicStringArrayKeyRector.md
        $this->assertSame('Password_confirmation is required.', $errors['password_confirmation_error']);
    }

    #[Test]
    public function invalidEmailProducesError(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'Jane' is a value of RegisterRequest::$name. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::name => 'Jane',
            RegisterRequest::email => 'not-valid',
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'securepass' is a value of RegisterRequest::$password. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::password => 'securepass',
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'securepass' is a value of RegisterRequest::$password_confirmation. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::password_confirmation => 'securepass',
        ]));

        $this->assertArrayNotHasKey('name_error', array: $errors);
        // TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value 'email_error' on the appropriate class. See: utils/rector/docs/ForbidMagicStringArrayKeyRector.md
        $this->assertSame('Enter a valid email address.', $errors['email_error']);
        $this->assertArrayNotHasKey('password_error', array: $errors);
    }

    #[Test]
    public function shortPasswordProducesError(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'Jane' is a value of RegisterRequest::$name. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::name => 'Jane',
            RegisterRequest::email => 'jane@example.com',
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'short' is a value of RegisterRequest::$password. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::password => 'short',
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'short' is a value of RegisterRequest::$password_confirmation. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::password_confirmation => 'short',
        ]));

        $this->assertArrayNotHasKey('name_error', array: $errors);
        $this->assertArrayNotHasKey('email_error', array: $errors);
        // TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value 'password_error' on the appropriate class. See: utils/rector/docs/ForbidMagicStringArrayKeyRector.md
        $this->assertSame('Password must be at least 8 characters.', $errors['password_error']);
    }

    #[Test]
    public function mismatchedPasswordsProduceError(): void
    {
        $errors = Validator::validate(RegisterRequest::from([
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'Jane' is a value of RegisterRequest::$name. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::name => 'Jane',
            RegisterRequest::email => 'jane@example.com',
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'securepass' is a value of RegisterRequest::$password. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::password => 'securepass',
            // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'different' is a value of RegisterRequest::$password_confirmation. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
            RegisterRequest::password_confirmation => 'different',
        ]));

        $this->assertArrayNotHasKey('name_error', array: $errors);
        $this->assertArrayNotHasKey('email_error', array: $errors);
        $this->assertArrayNotHasKey('password_error', array: $errors);
        // TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value 'password_confirmation_error' on the appropriate class. See: utils/rector/docs/ForbidMagicStringArrayKeyRector.md
        $this->assertSame('Password does not match.', $errors['password_confirmation_error']);
    }
}
