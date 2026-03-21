<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Validation\Rule;

final class RuleTest extends TestCase
{
    private const string matching_value = 'secret';
    #[Test]
    public function requiredPassesWithNonEmptyValue(): void
    {
        $this->assertTrue(Rule::required->passes('hello', null, new stdClass()));
        $this->assertFalse(Rule::required->passes('', null, new stdClass()));
        $this->assertSame('Name is required.', Rule::required->message(RegisterRequest::name, null));
    }

    #[Test]
    public function emailPassesWithValidEmail(): void
    {
        $this->assertTrue(Rule::email->passes('a@b.com', null, new stdClass()));
        $this->assertFalse(Rule::email->passes('not-an-email', null, new stdClass()));
        $this->assertSame('Enter a valid email address.', Rule::email->message(RegisterRequest::email, null));
    }

    #[Test]
    public function minPassesWhenLengthMeetsThreshold(): void
    {
        $this->assertTrue(Rule::min->passes('12345678', 8, new stdClass()));
        $this->assertFalse(Rule::min->passes('1234567', 8, new stdClass()));
        $this->assertSame('Password must be at least 8 characters.', Rule::min->message(RegisterRequest::password, 8));
    }

    #[Test]
    public function maxPassesWhenLengthWithinLimit(): void
    {
        $this->assertTrue(Rule::max->passes('abc', 5, new stdClass()));
        $this->assertFalse(Rule::max->passes('abcdef', 5, new stdClass()));
        $this->assertSame('Name must be at most 5 characters.', Rule::max->message(RegisterRequest::name, 5));
    }

    #[Test]
    public function matchesPassesWhenFieldsEqual(): void
    {
        $stdClass = (object) [
            RegisterRequest::password => self::matching_value];
        $this->assertTrue(Rule::matches->passes(self::matching_value, RegisterRequest::password, context: $stdClass));
        $this->assertFalse(Rule::matches->passes('different', RegisterRequest::password, context: $stdClass));
        $this->assertSame('Password does not match.', Rule::matches->message(RegisterRequest::password_confirmation, RegisterRequest::password));
    }
}
