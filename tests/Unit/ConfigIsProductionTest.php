<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\APP_ENV;
use ZeroToProd\Thryds\Config;

final class ConfigIsProductionTest extends TestCase
{
    #[Test]
    public function trueWhenAppEnvIsProduction(): void
    {
        $this->assertTrue(Config::from([Config::APP_ENV => APP_ENV::production->value])->isProduction());
    }

    #[Test]
    public function falseWhenAppEnvIsNotProduction(): void
    {
        $this->assertFalse(Config::from([Config::APP_ENV => APP_ENV::development->value])->isProduction());
    }
}
