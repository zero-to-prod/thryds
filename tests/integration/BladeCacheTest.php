<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use Illuminate\Container\Container;
use Jenssegers\Blade\Blade;
use Jenssegers\Blade\Container as BladeContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\Helpers\View;

final class BladeCacheTest extends TestCase
{
    private string $cache_dir;
    private string $template_dir;

    protected function setUp(): void
    {
        $this->cache_dir = sys_get_temp_dir() . '/blade_cache_test_' . uniqid('', true);
        $this->template_dir = dirname(__DIR__, 2) . '/templates';
        mkdir($this->cache_dir, 0o755, true);
    }

    protected function tearDown(): void
    {
        // Clean up cached files
        foreach (glob($this->cache_dir . '/*.php') as $file) {
            unlink(filename: $file);
        }
        rmdir($this->cache_dir);
    }

    #[Test]
    public function compiledTemplatesAreCachedToDisk(): void
    {
        $Container = new BladeContainer();
        Container::setInstance(container: $Container);
        $Blade = new Blade(viewPaths: $this->template_dir, cachePath: $this->cache_dir, container: $Container);

        $this->assertSame([], glob($this->cache_dir . '/*.php'), 'Cache dir should start empty');

        $Blade->make(view: View::home)->render();
        $cached_files = glob($this->cache_dir . '/*.php');

        $this->assertNotEmpty(actual: $cached_files, message: 'Compiled templates should be written to cache dir');
    }

    #[Test]
    public function secondRenderUsesCache(): void
    {
        $Container = new BladeContainer();
        Container::setInstance(container: $Container);
        $Blade = new Blade(viewPaths: $this->template_dir, cachePath: $this->cache_dir, container: $Container);

        // First render — compiles and caches
        $first_html = $Blade->make(view: View::home)->render();
        $cached_files = glob($this->cache_dir . '/*.php');
        $mtimes = [];
        foreach ($cached_files as $file) {
            $mtimes[$file] = filemtime(filename: $file);
        }

        // Ensure filesystem timestamp granularity (1 second)
        sleep(1);

        // Second render — should reuse cached files
        $second_html = $Blade->make(view: View::home)->render();

        $this->assertSame(expected: $first_html, actual: $second_html, message: 'Output should be identical');

        foreach ($cached_files as $file) {
            $this->assertSame(
                $mtimes[$file],
                filemtime(filename: $file),
                "Cached file should not be recompiled: $file",
            );
        }
    }
}
