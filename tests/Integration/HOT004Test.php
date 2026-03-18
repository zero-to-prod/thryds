<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Env;
use ZeroToProd\Thryds\Routes\Route;

// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'idiomorph' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'frankenphp-hot-reload:url' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
final class HOT004Test extends IntegrationTestCase
{
    #[Test]
    // Criterion: HOT-004-a — When FRANKENPHP_HOT_RELOAD is not set, the rendered HTML contains no hot-reload meta tag or CDN script tags
    public function test_HOT_004_a(): void
    {
        unset($_SERVER[Env::FRANKENPHP_HOT_RELOAD]);

        $body = (string) $this->get(Route::home)->getBody();

        $this->assertStringNotContainsString('frankenphp-hot-reload:url', haystack: $body);
        $this->assertStringNotContainsString('idiomorph', haystack: $body);
        $this->assertStringNotContainsString('frankenphp-hot-reload', haystack: $body);
    }

    #[Test]
    // Criterion: HOT-004-b — When FRANKENPHP_HOT_RELOAD is set, the rendered HTML includes the Mercure topic meta tag and both CDN script tags
    public function test_HOT_004_b(): void
    {
        $_SERVER[Env::FRANKENPHP_HOT_RELOAD] = '/.well-known/mercure?topic=test';

        $body = (string) $this->get(Route::home)->getBody();

        $this->assertStringContainsString('frankenphp-hot-reload:url', haystack: $body);
        $this->assertStringContainsString('idiomorph', haystack: $body);
        $this->assertStringContainsString('frankenphp-hot-reload/+esm', haystack: $body);
    }

    protected function tearDown(): void
    {
        unset($_SERVER[Env::FRANKENPHP_HOT_RELOAD]);
        parent::tearDown();
    }
}
