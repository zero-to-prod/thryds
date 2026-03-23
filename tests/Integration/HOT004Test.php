<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Framework\Env;
use ZeroToProd\Thryds\Routes\RouteList;

final class HOT004Test extends IntegrationTestCase
{
    private const string meta_mercure_url = 'frankenphp-hot-reload:url';

    private const string script_idiomorph = 'idiomorph';

    #[Test]
    // Criterion: HOT-004-a — When FRANKENPHP_HOT_RELOAD is not set, the rendered HTML contains no hot-reload meta tag or CDN script tags
    public function test_HOT_004_a(): void
    {
        unset($_SERVER[Env::FRANKENPHP_HOT_RELOAD]);

        $body = (string) $this->get(RouteList::home)->getBody();

        $this->assertStringNotContainsString(self::meta_mercure_url, haystack: $body);
        $this->assertStringNotContainsString(self::script_idiomorph, haystack: $body);
        $this->assertStringNotContainsString('frankenphp-hot-reload', haystack: $body);
    }

    #[Test]
    // Criterion: HOT-004-b — When FRANKENPHP_HOT_RELOAD is set, the rendered HTML includes the Mercure topic meta tag and both CDN script tags
    public function test_HOT_004_b(): void
    {
        $_SERVER[Env::FRANKENPHP_HOT_RELOAD] = '/.well-known/mercure?topic=test';

        $body = (string) $this->get(RouteList::home)->getBody();

        $this->assertStringContainsString(self::meta_mercure_url, haystack: $body);
        $this->assertStringContainsString(self::script_idiomorph, haystack: $body);
        $this->assertStringContainsString('frankenphp-hot-reload/+esm', haystack: $body);
    }

    protected function tearDown(): void
    {
        unset($_SERVER[Env::FRANKENPHP_HOT_RELOAD]);
        parent::tearDown();
    }
}
