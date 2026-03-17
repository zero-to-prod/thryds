<?php

declare(strict_types=1);

/**
 * Verifies that League\Route\Cache\Router caches routes in production mode.
 *
 * Usage: docker compose exec php php /app/scripts/verify-route-cache.php
 * Via Composer: ./run check:route-cache
 *
 * Proves caching by tracking whether the builder callable is invoked.
 * In production mode:
 *   - First dispatch: builder runs, cache file is written.
 *   - Second dispatch: builder is skipped, router is loaded from cache.
 * In development mode:
 *   - Builder runs on every dispatch, no cache file is written.
 */

require __DIR__ . '/../vendor/autoload.php';

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use League\Route\Cache\FileCache;
use League\Route\Cache\Router as CachedRouter;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;

$cache_file = __DIR__ . '/../var/cache/route-verify.cache';
$failures = [];
$passes = [];

// Clean slate
if (file_exists($cache_file)) {
    unlink($cache_file);
}

$request = new ServerRequest(serverParams: [], uploadedFiles: [], uri: new Uri('http://localhost/'), method: 'GET');

// -- Production mode: cacheEnabled = true --

$build_count = 0;
$builder = static function (Router $Router) use (&$build_count): Router {
    $build_count++;
    $Router->map('GET', '/', static fn(): ResponseInterface => new \Laminas\Diactoros\Response\HtmlResponse('ok'));

    return $Router;
};

$CachedRouter = new CachedRouter(
    builder: $builder,
    cache: new FileCache(cacheFilePath: $cache_file, ttl: 86400),
    cacheEnabled: true,
);

// First dispatch — builder must run and cache file must be created
$CachedRouter->dispatch($request);

if ($build_count !== 1) {
    $failures[] = sprintf('Production first dispatch: builder ran %d times, expected 1', $build_count);
} else {
    $passes[] = 'Production first dispatch: builder ran once';
}

if (!file_exists($cache_file)) {
    $failures[] = 'Production first dispatch: cache file was not created';
} else {
    $cache_size = filesize($cache_file);
    $passes[] = sprintf('Production first dispatch: cache file created (%d bytes)', $cache_size);

    $cache_contents = file_get_contents($cache_file);
    $unserialized = unserialize($cache_contents, ['allowed_classes' => true]);
    if ($unserialized instanceof Router) {
        $passes[] = 'Production cache contains a valid League\Route\Router instance';
    } else {
        $failures[] = sprintf('Production cache contains %s, expected League\Route\Router', get_debug_type($unserialized));
    }
}

// Second dispatch — builder must NOT run (served from cache)
$CachedRouter2 = new CachedRouter(
    builder: $builder,
    cache: new FileCache(cacheFilePath: $cache_file, ttl: 86400),
    cacheEnabled: true,
);

$CachedRouter2->dispatch($request);

if ($build_count !== 1) {
    $failures[] = sprintf('Production second dispatch: builder ran %d times total, expected 1 (cache was not used)', $build_count);
} else {
    $passes[] = 'Production second dispatch: builder was skipped (served from cache)';
}

// Clean up production cache
if (file_exists($cache_file)) {
    unlink($cache_file);
}

// -- Development mode: cacheEnabled = false --

$dev_build_count = 0;
$dev_builder = static function (Router $Router) use (&$dev_build_count): Router {
    $dev_build_count++;
    $Router->map('GET', '/', static fn(): ResponseInterface => new \Laminas\Diactoros\Response\HtmlResponse('ok'));

    return $Router;
};

$DevRouter = new CachedRouter(
    builder: $dev_builder,
    cache: new FileCache(cacheFilePath: $cache_file, ttl: 86400),
    cacheEnabled: false,
);

$DevRouter->dispatch($request);

if ($dev_build_count !== 1) {
    $failures[] = sprintf('Development first dispatch: builder ran %d times, expected 1', $dev_build_count);
} else {
    $passes[] = 'Development first dispatch: builder ran once';
}

if (file_exists($cache_file)) {
    $failures[] = 'Development mode: cache file was created (should not be)';
} else {
    $passes[] = 'Development mode: no cache file created';
}

$DevRouter2 = new CachedRouter(
    builder: $dev_builder,
    cache: new FileCache(cacheFilePath: $cache_file, ttl: 86400),
    cacheEnabled: false,
);

$DevRouter2->dispatch($request);

if ($dev_build_count !== 2) {
    $failures[] = sprintf('Development second dispatch: builder ran %d times total, expected 2', $dev_build_count);
} else {
    $passes[] = 'Development second dispatch: builder ran again (no caching)';
}

// Clean up
if (file_exists($cache_file)) {
    unlink($cache_file);
}

// -- Report --

echo "\n=== Route Cache Verification ===\n\n";

if ($failures !== []) {
    echo "FAILURES:\n";
    foreach ($failures as $f) {
        echo "  [FAIL] $f\n";
    }
    echo "\n";
}

if ($passes !== []) {
    echo "PASSING:\n";
    foreach ($passes as $p) {
        echo "  [ OK ] $p\n";
    }
    echo "\n";
}

$total = count($failures) + count($passes);
echo sprintf("Result: %d checks — %d failed, %d passed\n", $total, count($failures), count($passes));

if ($failures !== []) {
    echo "Verdict: Route caching is NOT working\n\n";
    exit(1);
}

echo "Verdict: Route caching is working correctly\n\n";
exit(0);
