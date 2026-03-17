<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Helpers\View;

final class BladeCacheTest extends IntegrationTestCase
{
    private const string php_glob = '/*.php';

    #[Test]
    public function compiledTemplatesAreCachedToDisk(): void
    {
        $this->assertSame([], glob($this->cache_dir . self::php_glob), 'Cache dir should start empty');

        $this->App->Blade->make(view: View::home->value)->render();

        $this->assertNotEmpty(actual: glob($this->cache_dir . self::php_glob), message: 'Compiled templates should be written to cache dir');
    }

    #[Test]
    public function secondRenderUsesCache(): void
    {
        // First render — compiles and caches
        $first_html = $this->App->Blade->make(view: View::home->value)->render();
        $cached_files = glob($this->cache_dir . self::php_glob);
        $mtimes = [];
        foreach ($cached_files as $file) {
            $mtimes[$file] = filemtime(filename: $file);
        }

        // Ensure filesystem timestamp granularity (1 second)
        sleep(1);

        $this->assertSame(expected: $first_html, actual: $this->App->Blade->make(view: View::home->value)->render(), message: 'Output should be identical');

        foreach ($cached_files as $file) {
            $this->assertSame(
                $mtimes[$file],
                filemtime(filename: $file),
                "Cached file should not be recompiled: $file",
            );
        }
    }
}
