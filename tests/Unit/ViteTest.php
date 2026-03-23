<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Framework\AppEnv;
use ZeroToProd\Framework\Blade\Vite;
use ZeroToProd\Framework\Config;
use ZeroToProd\Framework\ConfigKey;

final class ViteTest extends TestCase
{
    private string $base_dir;

    protected function setUp(): void
    {
        $this->base_dir = dirname(__DIR__, 2);
    }

    #[Test]
    public function returnsProductionTagsWithCssAndJsFromManifest(): void
    {
        $this->assertStringNotContainsString('localhost:5173', haystack: new Vite(Config::from([ConfigKey::AppEnv->value => AppEnv::production->value]), baseDir: $this->base_dir)->tags(Vite::app_entry));
    }

    #[Test]
    public function returnsDevTagsWithLocalViteServerWhenNotProduction(): void
    {
        $tags = new Vite(Config::from([ConfigKey::AppEnv->value => AppEnv::development->value]), baseDir: $this->base_dir, entry_css: [
            Vite::app_entry => [Vite::app_css],
        ])->tags(Vite::app_entry);

        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/@vite/client"></script>', haystack: $tags);
        $this->assertStringContainsString('<link rel="stylesheet" href="http://localhost:5173/' . Vite::app_css . '">', haystack: $tags);
        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/' . Vite::app_entry . '"></script>', haystack: $tags);
        $this->assertStringNotContainsString('/build/', haystack: $tags);
    }

    #[Test]
    public function returnsEmptyStringWhenManifestEntryIsMissing(): void
    {
        $this->assertSame('', new Vite(Config::from([ConfigKey::AppEnv->value => AppEnv::production->value]), baseDir: $this->base_dir)->tags('resources/js/nonexistent.js'));
    }

    #[Test]
    public function directivePhpGeneratesRuntimeContainerResolution(): void
    {
        $php = new Vite(Config::from([ConfigKey::AppEnv->value => AppEnv::production->value]), baseDir: $this->base_dir)->directivePhp(Vite::app_entry);

        $this->assertStringContainsString('Container::getInstance()->make(', haystack: $php);
        $this->assertStringContainsString(Vite::class . '::class', haystack: $php);
        $this->assertStringContainsString(Vite::app_entry, haystack: $php);
        $this->assertStringStartsWith('<?php', string: $php);
    }
}
