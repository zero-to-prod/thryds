<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Config;

final class ConfigIsProductionTest extends TestCase
{
    #[Test]
    public function trueWhenAppEnvIsProduction(): void
    {
        $Config = Config::from([Config::appEnv => AppEnv::production->value]);
        $this->assertTrue($Config->isProduction());
    }

    #[Test]
    public function trueWhenAppEnvIsMissing(): void
    {
        $Config = Config::from([Config::appEnv => AppEnv::production->value]);
        $this->assertTrue($Config->isProduction());
    }

    #[Test]
    public function falseWhenAppEnvIsNotProduction(): void
    {
        $Config = Config::from([Config::appEnv => AppEnv::development->value]);
        $this->assertFalse($Config->isProduction());
    }
}
