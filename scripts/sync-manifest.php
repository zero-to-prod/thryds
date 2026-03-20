<?php

declare(strict_types=1);

/**
 * Scaffold code for entities declared in thryds.yaml but missing from the codebase.
 *
 * Only handles missing_from_code entries. Does NOT modify existing code to fix
 * property_drift — that requires human/agent judgment.
 *
 * Exit 0 on success. Exit 1 on errors.
 *
 * Usage: ./run sync:manifest
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/parse-manifest.php';
require __DIR__ . '/build-actual-graph.php';
require __DIR__ . '/manifest-diff.php';

$projectRoot  = realpath(__DIR__ . '/../') . '/';
$manifestPath = $projectRoot . 'thryds.yaml';

$desired = parseManifest($manifestPath);
$actual  = buildActualGraph($projectRoot);
$diff    = diffGraphs($desired, $actual);

$created = [];
$skipped = [];
$errors  = [];

foreach ($diff['missing_from_code'] as $item) {
    $section = $item['section'];
    $name    = $item['name'];
    $props   = $item['desired'];

    try {
        match ($section) {
            'views' => scaffoldView($name, $props, $projectRoot, $created),
            'components' => scaffoldComponent($name, $props, $projectRoot, $created),
            'viewmodels' => scaffoldViewModel($name, $props, $projectRoot, $created),
            'tests' => scaffoldTest($name, $props, $projectRoot, $created),
            'controllers' => scaffoldController($name, $props, $projectRoot, $created),
            default => $skipped[] = ['section' => $section, 'name' => $name, 'reason' => "No scaffold handler for $section"],
        };
    } catch (\Throwable $e) {
        $errors[] = ['section' => $section, 'name' => $name, 'error' => $e->getMessage()];
    }
}

$result = ['created' => $created, 'skipped' => $skipped, 'errors' => $errors];
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

if ($created !== []) {
    fwrite(STDERR, sprintf("Scaffolded %d entities.\n", count($created)));
}
if ($skipped !== []) {
    fwrite(STDERR, sprintf("Skipped %d entities (no handler).\n", count($skipped)));
}
if ($errors !== []) {
    fwrite(STDERR, sprintf("Errors: %d\n", count($errors)));
    exit(1);
}

exit(0);

// --- Scaffold functions ---

function scaffoldView(string $name, array $props, string $root, array &$created): void
{
    $templatePath = $root . 'templates/' . $name . '.blade.php';
    $files        = [];

    if (! file_exists($templatePath)) {
        $layout     = $props['layout'] ?? 'base';
        $title      = $props['title'] ?? ucfirst($name) . ' — Thryds';
        $viewModels = $props['viewmodels'] ?? [];

        $useLines = '';
        foreach ($viewModels as $vm) {
            $useLines .= "    use ZeroToProd\\Thryds\\ViewModels\\$vm;\n";
        }

        $template = "@php\n{$useLines}@endphp\n@extends('$layout')\n\n@section('title', '$title')\n\n@section('body')\n    {{-- TODO: implement $name view --}}\n@endsection\n";
        file_put_contents($templatePath, $template);
        $files[] = "templates/$name.blade.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'views', 'name' => $name, 'files' => $files];
    }
}

function scaffoldComponent(string $name, array $props, string $root, array &$created): void
{
    $value        = str_replace('_', '-', $name);
    $templatePath = $root . 'templates/components/' . $value . '.blade.php';
    $files        = [];

    if (! file_exists($templatePath)) {
        $propsLines = '';
        foreach (($props['props'] ?? []) as $propName => $propDef) {
            $default    = $propDef['default'] ?? "''";
            $propsLines .= "    '$propName' => '$default',\n";
        }
        $template = "@props([\n{$propsLines}])\n<div {{ \$attributes }}>\n    {{ \$slot }}\n</div>\n";
        file_put_contents($templatePath, $template);
        $files[] = "templates/components/$value.blade.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'components', 'name' => $name, 'files' => $files];
    }
}

function scaffoldViewModel(string $name, array $props, string $root, array &$created): void
{
    $classPath = $root . 'src/ViewModels/' . $name . '.php';
    $files     = [];

    if (! file_exists($classPath)) {
        $fields = $props['fields'] ?? [];
        $consts = '';
        $properties = '';
        foreach ($fields as $fieldName => $fieldType) {
            $consts     .= "    public const string $fieldName = '$fieldName';\n";
            $properties .= "    public $fieldType \$$fieldName;\n";
        }

        $class = <<<PHP
            <?php

            declare(strict_types=1);

            namespace ZeroToProd\\Thryds\\ViewModels;

            use ZeroToProd\\Thryds\\Attributes\\DataModel;
            use ZeroToProd\\Thryds\\Attributes\\ViewModel;

            #[ViewModel]
            readonly class $name
            {
                use DataModel;

            $consts
            $properties}

            PHP;

        // Remove common leading whitespace from heredoc
        $class = preg_replace('/^ {12}/m', '', $class);
        file_put_contents($classPath, $class);
        $files[] = "src/ViewModels/$name.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'viewmodels', 'name' => $name, 'files' => $files];
    }
}

function scaffoldTest(string $name, array $props, string $root, array &$created): void
{
    $classPath = $root . 'tests/Integration/' . $name . '.php';
    $files     = [];

    if (! file_exists($classPath)) {
        $coversRoutes = $props['covers_routes'] ?? [];
        $useLines     = "use PHPUnit\\Framework\\Attributes\\Test;\n";
        $attrLine     = '';
        if ($coversRoutes !== []) {
            $useLines .= "use ZeroToProd\\Thryds\\Attributes\\CoversRoute;\n";
            $useLines .= "use ZeroToProd\\Thryds\\Routes\\Route;\n";
            $routeArgs = implode(', ', array_map(fn(string $r): string => "Route::$r", $coversRoutes));
            $attrLine  = "#[CoversRoute($routeArgs)]\n";
        }

        $class = <<<PHP
            <?php

            declare(strict_types=1);

            namespace ZeroToProd\\Thryds\\Tests\\Integration;

            $useLines
            {$attrLine}final class $name extends IntegrationTestCase
            {
                #[Test]
                public function stub(): void
                {
                    \$this->assertTrue(true);
                }
            }

            PHP;

        $class = preg_replace('/^ {12}/m', '', $class);
        file_put_contents($classPath, $class);
        $files[] = "tests/Integration/$name.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'tests', 'name' => $name, 'files' => $files];
    }
}

function scaffoldController(string $name, array $props, string $root, array &$created): void
{
    $classPath = $root . 'src/Controllers/' . $name . '.php';
    $files     = [];

    if (! file_exists($classPath)) {
        $renders    = $props['renders'] ?? null;
        $persists   = $props['persists'] ?? [];
        $redirectsTo = $props['redirects_to'] ?? [];

        $useLines = '';
        $attrs    = '';
        foreach ($persists as $model) {
            $useLines .= "use ZeroToProd\\Thryds\\Attributes\\Persists;\n";
            $useLines .= "use ZeroToProd\\Thryds\\Tables\\$model;\n";
            $attrs    .= "#[Persists($model::class)]\n";
        }
        foreach ($redirectsTo as $route) {
            $useLines .= "use ZeroToProd\\Thryds\\Attributes\\RedirectsTo;\n";
            $useLines .= "use ZeroToProd\\Thryds\\Routes\\Route;\n";
            $attrs    .= "#[RedirectsTo(Route::$route)]\n";
        }

        $class = <<<PHP
            <?php

            declare(strict_types=1);

            namespace ZeroToProd\\Thryds\\Controllers;

            use Psr\\Http\\Message\\ResponseInterface;
            use Psr\\Http\\Message\\ServerRequestInterface;
            $useLines
            {$attrs}class $name
            {
                public function __invoke(ServerRequestInterface \$request): ResponseInterface
                {
                    // TODO: implement $name
                    throw new \\RuntimeException('Not implemented');
                }
            }

            PHP;

        $class = preg_replace('/^ {12}/m', '', $class);
        file_put_contents($classPath, $class);
        $files[] = "src/Controllers/$name.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'controllers', 'name' => $name, 'files' => $files];
    }
}
