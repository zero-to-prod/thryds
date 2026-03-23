<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Framework\Validation\Rule;
use ZeroToProd\Thryds\Requests\RegisterRequest;

final class RuleTest extends TestCase
{
    #[Test]
    public function requiredPassesWithNonEmptyValue(): void
    {
        $this->assertTrue(Rule::required->passes('hello', null));
        $this->assertFalse(Rule::required->passes('', null));
        $this->assertSame('Name is required.', Rule::required->message(RegisterRequest::name, null));
    }

    #[Test]
    public function emailPassesWithValidEmail(): void
    {
        $this->assertTrue(Rule::email->passes('a@b.com', null));
        $this->assertFalse(Rule::email->passes('not-an-email', null));
        $this->assertSame('Enter a valid email address.', Rule::email->message(RegisterRequest::email, null));
    }

    #[Test]
    public function minPassesWhenLengthMeetsThreshold(): void
    {
        $this->assertTrue(Rule::min->passes('12345678', 8));
        $this->assertFalse(Rule::min->passes('1234567', 8));
        $this->assertSame('Password must be at least 8 characters.', Rule::min->message(RegisterRequest::password, 8));
    }

    #[Test]
    public function maxPassesWhenLengthWithinLimit(): void
    {
        $this->assertTrue(Rule::max->passes('abc', 5));
        $this->assertFalse(Rule::max->passes('abcdef', 5));
        $this->assertSame('Name must be at most 5 characters.', Rule::max->message(RegisterRequest::name, 5));
    }
}
