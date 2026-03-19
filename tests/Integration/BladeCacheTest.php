<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Blade\View;

final class BladeCacheTest extends IntegrationTestCase
{
    private const string php_glob = '/*.php';
    private const string body_fragment = 'body';

    #[Test]
    public function compiledTemplatesAreCachedToDisk(): void
    {
        $this->assertSame([], glob($this->cache_dir . self::php_glob), 'Cache dir should start empty');

        $this->App->Blade->make(view: View::home->value)->fragmentIf(false, self::body_fragment);

        $this->assertNotEmpty(actual: glob($this->cache_dir . self::php_glob), message: 'Compiled templates should be written to cache dir');
    }

    #[Test]
    public function secondRenderUsesCache(): void
    {
        // First render — compiles and caches
        $first_html = $this->App->Blade->make(view: View::home->value)->fragmentIf(false, self::body_fragment);
        $cached_files = glob($this->cache_dir . self::php_glob);
        $hashes = [];
        foreach ($cached_files as $file) {
            $hashes[$file] = md5_file(filename: $file);
        }

        $this->assertSame(expected: $first_html, actual: $this->App->Blade->make(view: View::home->value)->fragmentIf(false, self::body_fragment), message: 'Output should be identical');
        $this->assertSame(expected: $cached_files, actual: glob($this->cache_dir . self::php_glob), message: 'No new compiled files should be created on second render');

        foreach ($cached_files as $file) {
            $this->assertSame(
                $hashes[$file],
                md5_file(filename: $file),
                "Cached file should not be recompiled: $file",
            );
        }
    }
}
