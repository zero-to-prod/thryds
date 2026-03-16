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
        $this->assertTrue(Config::isProduction(null, [Config::appEnv => AppEnv::Production->value]));
    }

    #[Test]
    public function trueWhenAppEnvIsMissing(): void
    {
        $this->assertTrue(Config::isProduction(null, []));
    }

    #[Test]
    public function falseWhenAppEnvIsNotProduction(): void
    {
        $this->assertFalse(Config::isProduction(null, [Config::appEnv => 'development']));
    }
}
