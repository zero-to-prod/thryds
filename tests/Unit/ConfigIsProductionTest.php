<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\ConfigKey;

final class ConfigIsProductionTest extends TestCase
{
    #[Test]
    public function trueWhenAppEnvIsProduction(): void
    {
        $this->assertTrue(Config::from([ConfigKey::AppEnv->value => AppEnv::production->value])->isProduction());
    }

    #[Test]
    public function falseWhenAppEnvIsNotProduction(): void
    {
        $this->assertFalse(Config::from([ConfigKey::AppEnv->value => AppEnv::development->value])->isProduction());
    }
}
