<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use Illuminate\Container\Container;
use Jenssegers\Blade\Blade;
use Jenssegers\Blade\Container as BladeContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\APP_ENV;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Helpers\View;
use ZeroToProd\Thryds\Helpers\Vite;

final class BladeCacheTest extends TestCase
{
    private const string php_glob = '/*.php';
    private const string base_dir = __DIR__ . '/../..';
    private const string vite = 'vite';
    private const string htmx = 'htmx';
    private string $cache_dir;
    private string $template_dir;

    protected function setUp(): void
    {
        $this->cache_dir = sys_get_temp_dir() . '/blade_cache_test_' . uniqid('', more_entropy: true);
        $this->template_dir = self::base_dir . '/templates';
        mkdir($this->cache_dir, 0o755, recursive: true);
    }

    protected function tearDown(): void
    {
        // Clean up cached files
        foreach (glob($this->cache_dir . self::php_glob) as $file) {
            unlink(filename: $file);
        }
        rmdir($this->cache_dir);
    }

    private function makeBlade(): Blade
    {
        $Container = new BladeContainer();
        Container::setInstance(container: $Container);
        $Blade = new Blade(viewPaths: $this->template_dir, cachePath: $this->cache_dir, container: $Container);
        $Vite = new Vite(Config::from([Config::APP_ENV => APP_ENV::development->value]), baseDir: self::base_dir, entry_css: [
            Vite::app_entry => [Vite::app_css],
        ]);
        $Blade->directive(self::vite, static fn(): string => $Vite->directivePhp(Vite::app_entry));
        $Blade->directive(self::htmx, static fn(): string => $Vite->directivePhp(Vite::htmx_entry));

        return $Blade;
    }

    #[Test]
    public function compiledTemplatesAreCachedToDisk(): void
    {
        $Blade = $this->makeBlade();

        $this->assertSame([], glob($this->cache_dir . self::php_glob), 'Cache dir should start empty');

        $Blade->make(view: View::home)->render();

        $this->assertNotEmpty(actual: glob($this->cache_dir . self::php_glob), message: 'Compiled templates should be written to cache dir');
    }

    #[Test]
    public function secondRenderUsesCache(): void
    {
        $Blade = $this->makeBlade();

        // First render — compiles and caches
        $first_html = $Blade->make(view: View::home)->render();
        $cached_files = glob($this->cache_dir . self::php_glob);
        $mtimes = [];
        foreach ($cached_files as $file) {
            $mtimes[$file] = filemtime(filename: $file);
        }

        // Ensure filesystem timestamp granularity (1 second)
        sleep(1);

        $this->assertSame(expected: $first_html, actual: $Blade->make(view: View::home)->render(), message: 'Output should be identical');

        foreach ($cached_files as $file) {
            $this->assertSame(
                $mtimes[$file],
                filemtime(filename: $file),
                "Cached file should not be recompiled: $file",
            );
        }
    }
}
