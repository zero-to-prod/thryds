<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\APP_ENV;
use ZeroToProd\Thryds\Config;

final class ConfigIsProductionTest extends TestCase
{
    #[Test]
    public function trueWhenAppEnvIsProduction(): void
    {
        $Config = Config::from([Config::APP_ENV => APP_ENV::production->value]);
        $this->assertTrue($Config->isProduction());
    }

    #[Test]
    public function falseWhenAppEnvIsNotProduction(): void
    {
        $Config = Config::from([Config::APP_ENV => APP_ENV::development->value]);
        $this->assertFalse($Config->isProduction());
    }
}
