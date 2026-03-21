<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use ZeroToProd\Thryds\Validation\Rule;

// TODO: [DetectParallelBladePhpBehaviorRector] Use ZeroToProd\Thryds\Requests\RegisterRequest::password instead of hardcoded 'password'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md
// TODO: [DetectParallelBladePhpBehaviorRector] Use ZeroToProd\Thryds\Requests\RegisterRequest::name instead of hardcoded 'name'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md
// TODO: [DetectParallelBladePhpBehaviorRector] Use ZeroToProd\Thryds\Requests\RegisterRequest::password_confirmation instead of hardcoded 'password_confirmation'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md
// TODO: [DetectParallelBladePhpBehaviorRector] Use ZeroToProd\Thryds\Validation\Rule::email instead of hardcoded 'email'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'secret' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'password' (used 5x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'name' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
final class RuleTest extends TestCase
{
    #[Test]
    public function requiredPassesWithNonEmptyValue(): void
    {
        $this->assertTrue(Rule::required->passes('hello', null, new stdClass()));
        $this->assertFalse(Rule::required->passes('', null, new stdClass()));
        $this->assertSame('Name is required.', Rule::required->message('name', null));
    }

    #[Test]
    public function emailPassesWithValidEmail(): void
    {
        $this->assertTrue(Rule::email->passes('a@b.com', null, new stdClass()));
        $this->assertFalse(Rule::email->passes('not-an-email', null, new stdClass()));
        $this->assertSame('Enter a valid email address.', Rule::email->message('email', null));
    }

    #[Test]
    public function minPassesWhenLengthMeetsThreshold(): void
    {
        $this->assertTrue(Rule::min->passes('12345678', 8, new stdClass()));
        $this->assertFalse(Rule::min->passes('1234567', 8, new stdClass()));
        $this->assertSame('Password must be at least 8 characters.', Rule::min->message('password', 8));
    }

    #[Test]
    public function maxPassesWhenLengthWithinLimit(): void
    {
        $this->assertTrue(Rule::max->passes('abc', 5, new stdClass()));
        $this->assertFalse(Rule::max->passes('abcdef', 5, new stdClass()));
        $this->assertSame('Name must be at most 5 characters.', Rule::max->message('name', 5));
    }

    #[Test]
    public function matchesPassesWhenFieldsEqual(): void
    {
        $stdClass = (object) [// TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value 'password' on the appropriate class. See: utils/rector/docs/ForbidMagicStringArrayKeyRector.md
            'password' => 'secret'];
        $this->assertTrue(Rule::matches->passes('secret', 'password', context: $stdClass));
        $this->assertFalse(Rule::matches->passes('different', 'password', context: $stdClass));
        $this->assertSame('Password does not match.', Rule::matches->message('password_confirmation', 'password'));
    }
}
