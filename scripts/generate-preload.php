<?php

declare(strict_types=1);

/**
 * Generates preload.php by booting the app and discovering loaded scripts.
 *
 * Usage: ./run generate:preload
 * Build: RUN php scripts/generate-preload.php (in Dockerfile)
 *
 * Boots the app the same way public/index.php does, renders all templates,
 * then uses get_included_files() to discover every script the app needs.
 * No running server required — works during docker build.
 */

$base_dir = dirname(__DIR__);

require $base_dir . '/vendor/autoload.php';

use League\Route\Router;
use ZeroToProd\Thryds\App;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Blade\Vite;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Routes\RouteRegistrar;

require __DIR__ . '/cache-views.php';

// Boot the app (mirrors public/index.php boot phase)
echo "Booting app...\n";

$Config = Config::from([
    Config::AppEnv => $_ENV[Config::AppEnv] ?? AppEnv::production->value,
    Config::blade_cache_dir => $base_dir . '/var/cache/blade',
    Config::template_dir => $base_dir . '/templates',
]);

if (!is_dir($Config->blade_cache_dir) && !mkdir($Config->blade_cache_dir, 0o755, true)) {
    throw new RuntimeException(sprintf('Directory "%s" was not created', $Config->blade_cache_dir));
}

$Vite = new Vite($Config, baseDir: $base_dir, entry_css: [
    Vite::app_entry => [Vite::app_css],
]);
$Blade = App::bootBlade($Config, $Vite);

// Direct instantiation is intentional: this script runs at build time, not in the request path.
// ForbidDirectRouterInstantiationRector only applies to src/, public/, tests/.
$Router = new Router();
RouteRegistrar::register($Router, $Config);

// Compile all templates to blade cache and load view-layer dependencies
echo "Compiling templates...\n";
compileAllTemplates($Blade);

// Simulate request dispatch to load HTTP-layer dependencies
echo "Simulating request dispatch...\n";
$ServerRequest = \Laminas\Diactoros\ServerRequestFactory::fromGlobals();
try {
    $Response = $Router->dispatch(request: $ServerRequest);
    new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter()->emit(response: $Response);
} catch (\Throwable) {
    // Expected — no real server, just loading the classes
}

// Force-load classes not reachable via boot/dispatch simulation
new \Laminas\Diactoros\Response\JsonResponse(data: []);
\Laminas\Diactoros\ServerRequestFilter\IPRange::matches('127.0.0.1', '127.0.0.0/8');

// Force-load classes only reachable via full App::boot() / HTTP request path
new \League\Route\Cache\Router(
    builder: static fn(Router $r): Router => $r,
    cache: new \League\Route\Cache\FileCache(cacheFilePath: '/dev/null', ttl: 0),
    cacheEnabled: false,
);
class_exists(\ZeroToProd\Thryds\App::class);
new \ZeroToProd\Thryds\RequestId();
new \Illuminate\Support\Collection();
new \Illuminate\Support\Stringable('');
new \Illuminate\View\Compilers\ComponentTagCompiler();
class_exists(\Laravel\SerializableClosure\Support\ClosureStream::class);
class_exists(\Laravel\SerializableClosure\Support\ClosureScope::class);
class_exists(\Psr\SimpleCache\CacheInterface::class);

// Discover all loaded scripts (plus the entrypoint, which FrankenPHP loads directly)
$scripts = get_included_files();
$scripts[] = $base_dir . '/public/index.php';
echo sprintf("Discovered %d loaded scripts\n", count($scripts));

$app_scripts = filterAppScripts($scripts);
echo sprintf("Filtered to %d app scripts (excluded temp/cache/dev files)\n", count($app_scripts));

$ordered = resolveOrder($app_scripts);

$preload_path = $base_dir . '/preload.php';
writePreload($preload_path, $ordered);

echo sprintf("Wrote %s with %d scripts\n", $preload_path, count($ordered));

function filterAppScripts(array $scripts): array
{
    $filtered = [];

    foreach ($scripts as $path) {
        // Only /app/ paths (docker) or project-relative paths
        if (!str_starts_with($path, '/app/') && !str_starts_with($path, dirname(__DIR__) . '/')) {
            continue;
        }

        // Normalize to /app/ prefix for consistency
        $path = str_replace(dirname(__DIR__), '/app', $path);

        // Skip scripts/, dev vendors, cache, tests, utils
        if (str_contains($path, '/scripts/') || array_any(
            \ZeroToProd\Thryds\DevPath::cases(),
            fn($devPath): bool => str_contains(haystack: $path, needle: $devPath->value)
        )
        ) {
            continue;
        }

        $filtered[] = $path;
    }

    return array_unique($filtered);
}

/**
 * Resolves class dependency ordering so parents/interfaces/traits
 * are compiled before the classes that depend on them.
 */
function resolveOrder(array $scripts): array
{
    // Build a map of class name → script path
    $class_to_path = [];
    $path_to_deps = [];

    foreach ($scripts as $path) {
        $info = parseClassInfo($path);
        if ($info === null) {
            $path_to_deps[$path] = [];

            continue;
        }

        $class_to_path[$info['class']] = $path;
        $path_to_deps[$path] = $info['deps'];
    }

    // Topological sort
    $ordered = [];
    $visited = [];
    $visiting = [];

    $visit = function (string $path) use (&$visit, &$ordered, &$visited, &$visiting, $path_to_deps, $class_to_path): void {
        if (isset($visited[$path])) {
            return;
        }

        if (isset($visiting[$path])) {
            return; // Circular — break the cycle
        }

        $visiting[$path] = true;

        foreach ($path_to_deps[$path] ?? [] as $dep_class) {
            if (isset($class_to_path[$dep_class])) {
                $visit($class_to_path[$dep_class]);
            }
        }

        unset($visiting[$path]);
        $visited[$path] = true;
        $ordered[] = $path;
    };

    foreach (array_keys($path_to_deps) as $path) {
        $visit($path);
    }

    return $ordered;
}

/**
 * Parses a PHP file to extract the fully qualified class/interface/trait name
 * and its dependencies (extends, implements, use traits).
 */
function parseClassInfo(string $path): ?array
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        return null;
    }

    $tokens = token_get_all($contents);
    $namespace = '';
    $class_name = null;
    $deps = [];
    $count = count($tokens);

    foreach ($tokens as $i => $iValue) {
        if (!is_array($iValue)) {
            continue;
        }

        $token_id = $tokens[$i][0];

        // Namespace
        if ($token_id === T_NAMESPACE) {
            $namespace = parseQualifiedName($tokens, $i, $count);
        }

        // Class/interface/trait/enum declaration
        if (in_array($token_id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
            // Skip anonymous classes
            $next = findNextNonWhitespace($tokens, $i, $count);
            if ($next !== null && is_array($next) && $next[0] === T_STRING) {
                $class_name = $namespace !== '' ? $namespace . '\\' . $next[1] : $next[1];
            }
        }

        // extends
        if ($token_id === T_EXTENDS) {
            $dep = parseQualifiedName($tokens, $i, $count);
            if ($dep !== '') {
                $deps[] = resolveClassName($dep, $namespace, $contents);
            }

            // Multiple extends (interfaces): A extends B, C, D
            while (($comma_idx = findNextNonWhitespace($tokens, $i, $count)) !== null) {
                if (!is_array($comma_idx) && $comma_idx === ',') {
                    $dep = parseQualifiedName($tokens, $i, $count);
                    if ($dep !== '') {
                        $deps[] = resolveClassName($dep, $namespace, $contents);
                    }
                } else {
                    break;
                }
            }
        }

        // implements
        if ($token_id === T_IMPLEMENTS) {
            do {
                $dep = parseQualifiedName($tokens, $i, $count);
                if ($dep !== '') {
                    $deps[] = resolveClassName($dep, $namespace, $contents);
                }

                $next = findNextNonWhitespace($tokens, $i, $count);
                if ($next === null || (!is_array($next) && $next !== ',')) {
                    break;
                }
            } while (true);
        }

        // use (trait) — only inside class body
        if ($token_id === T_USE && $class_name !== null) {
            $dep = parseQualifiedName($tokens, $i, $count);
            if ($dep !== '') {
                $deps[] = resolveClassName($dep, $namespace, $contents);
            }
        }
    }

    if ($class_name === null) {
        return null;
    }

    return ['class' => $class_name, 'deps' => $deps];
}

function parseQualifiedName(array $tokens, int &$i, int $count): string
{
    $name = '';
    $i++;

    while ($i < $count) {
        if (is_array($tokens[$i])) {
            if ($tokens[$i][0] === T_WHITESPACE || $tokens[$i][0] === T_COMMENT) {
                $i++;

                continue;
            }

            if (in_array($tokens[$i][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NS_SEPARATOR], true)) {
                $name .= $tokens[$i][1];
                $i++;

                continue;
            }
        }

        break;
    }

    return $name;
}

function findNextNonWhitespace(array $tokens, int &$i, int $count): mixed
{
    $i++;

    while ($i < $count) {
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
            $i++;

            continue;
        }

        return $tokens[$i];
    }

    return null;
}

/**
 * Resolves a class name to fully qualified using the file's use statements.
 */
function resolveClassName(string $name, string $namespace, string $contents): string
{
    // Already fully qualified
    if (str_starts_with($name, '\\')) {
        return ltrim($name, '\\');
    }

    // Check use statements
    if (preg_match_all('/^use\s+([^;]+);/m', $contents, $matches)) {
        foreach ($matches[1] as $use_statement) {
            $use_statement = trim($use_statement);
            // Handle "use Foo\Bar as Baz"
            $parts = preg_split('/\s+as\s+/i', $use_statement);
            $fqcn = $parts[0];
            $alias = $parts[1] ?? basename(str_replace('\\', '/', $fqcn));

            $first_part = explode('\\', $name)[0];
            if ($alias === $first_part) {
                if ($first_part === $name) {
                    return $fqcn;
                }

                // Name is longer, e.g. "Foo\Bar" where Foo is aliased
                return $fqcn . substr($name, strlen($first_part));
            }
        }
    }

    // Same namespace
    if ($namespace !== '') {
        return $namespace . '\\' . $name;
    }

    return $name;
}

function writePreload(string $path, array $scripts): void
{
    $lines = ["<?php\n", "\ndeclare(strict_types=1);\n"];

    // Group scripts
    $groups = [
        'Autoload' => [],
        'Helpers' => [],
        'Core' => [],
        'Routes' => [],
        'ViewModels' => [],
        'Entrypoint' => [],
        'Vendor' => [],
    ];

    foreach ($scripts as $script) {
        $rel = str_replace('/app/', '', $script);

        if ($rel === 'vendor/autoload.php') {
            $groups['Autoload'][] = $script;
        } elseif (str_starts_with($rel, 'src/Helpers/')) {
            $groups['Helpers'][] = $script;
        } elseif (str_starts_with($rel, 'src/Routes/')) {
            $groups['Routes'][] = $script;
        } elseif (str_starts_with($rel, 'src/ViewModels/')) {
            $groups['ViewModels'][] = $script;
        } elseif (str_starts_with($rel, 'public/')) {
            $groups['Entrypoint'][] = $script;
        } elseif (str_starts_with($rel, 'src/')) {
            $groups['Core'][] = $script;
        } elseif (str_starts_with($rel, 'vendor/')) {
            $groups['Vendor'][] = $script;
        }
    }

    foreach ($groups as $label => $group_scripts) {
        if ($group_scripts === []) {
            continue;
        }

        $lines[] = "\n// $label";

        foreach ($group_scripts as $script) {
            $rel = str_replace('/app/', '', $script);
            $lines[] = sprintf("opcache_compile_file(__DIR__ . '/%s');", $rel);
        }
    }

    file_put_contents($path, implode("\n", $lines) . "\n");
}
