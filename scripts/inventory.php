<?php

declare(strict_types=1);

/**
 * Generate a dependency graph of routes → controllers → views → components/layouts.
 *
 * Outputs DOT (Graphviz) or JSON based on --format= argument.
 *
 * Usage: docker compose exec web php scripts/inventory.php [--format=dot|json]
 * Via Composer: ./run list:inventory [-- --format=dot]
 *
 * Exit 0 on success.
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Attributes\Persists;
use ZeroToProd\Thryds\Attributes\RedirectsTo;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\Attributes\Table;
use ZeroToProd\Thryds\Attributes\ViewModel;
use ZeroToProd\Thryds\Blade\Component;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Routes\Route;

// Parse --format= argument; default to json.
$format = 'json';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, strlen('--format='));
    }
}

$projectRoot  = realpath(__DIR__ . '/../') . '/';
$templatesDir = $projectRoot . 'templates';

/** Controllers explicitly mapped in RouteRegistrar (route name → short class name). */
$explicitControllers = [
    'home'     => 'HomeController',
    'register' => 'RegisterController',
];

/** All registered component names (the x- tag suffix). */
$componentNames = array_map(fn(Component $c): string => $c->value, Component::cases());

/** All route case names, for validating template references. */
$routeNames = array_map(fn(Route $r): string => $r->name, Route::cases());

/**
 * Parse @props([...]) from a component template and return structured prop definitions.
 *
 * Resolves enum-backed defaults (e.g. ButtonVariant::primary->value) to their string values.
 * Props without a resolvable enum default use null for the enum field.
 *
 * @return array<int, array{name: string, default: string, enum: string|null}>
 */
function parseComponentProps(string $source): array
{
    if (! preg_match('/@props\(\[(.*?)\]\)/s', $source, $block)) {
        return [];
    }

    $props = [];
    foreach (preg_split('/\r?\n/', $block[1]) as $line) {
        $line = trim($line);
        if (! preg_match('/^[\'"](\w+)[\'"]\s*=>\s*(.+?)(?:,)?\s*$/', $line, $m)) {
            continue;
        }

        $name  = $m[1];
        $value = trim($m[2]);

        if (preg_match('/^(\w+)::(\w+)->value$/', $value, $enumMatch)) {
            $enumName = $enumMatch[1];
            $caseName = $enumMatch[2];
            $fqcn     = 'ZeroToProd\\Thryds\\UI\\' . $enumName;
            try {
                $default = constant($fqcn . '::' . $caseName)->value;
            } catch (\Throwable) {
                $default = $caseName;
            }
            $props[] = ['name' => $name, 'default' => $default, 'enum' => $enumName];
        } else {
            $props[] = ['name' => $name, 'default' => trim($value, '\'"'), 'enum' => null];
        }
    }

    return $props;
}

/**
 * Parse a Blade template file and return its direct dependencies.
 *
 * @return array{layout:string|null, includes:string[], components:string[], view_models:string[]}
 */
function parseTemplate(string $path): array
{
    $source = file_get_contents($path);

    // @extends('name') or @extends("name")
    preg_match('/@extends\([\'"]([^\'"]+)[\'"]\)/', $source, $extendsMatch);
    $layout = $extendsMatch[1] ?? null;

    // @include('name') or @include("name")
    preg_match_all('/@include\([\'"]([^\'"]+)[\'"]\)/', $source, $includeMatches);
    $includes = $includeMatches[1] ?? [];

    // <x-name> tags — capture the component slug
    preg_match_all('/<x-([\w-]+)/', $source, $componentMatches);
    $components = array_values(array_unique($componentMatches[1] ?? []));

    // `use` statements inside @php blocks referencing the ViewModels namespace
    preg_match_all('/use\s+\S+\\\\ViewModels\\\\(\w+)\s*;/', $source, $viewModelMatches);
    $view_models = array_values(array_unique($viewModelMatches[1] ?? []));

    // `use` statements inside @php blocks referencing the UI namespace
    preg_match_all('/use\s+\S+\\\\UI\\\\(\w+)\s*;/', $source, $uiEnumMatches);
    $ui_enums = array_values(array_unique($uiEnumMatches[1] ?? []));

    // Route::caseName references anywhere in the template
    preg_match_all('/\bRoute::(\w+)\b/', $source, $routeRefMatches);
    $route_refs = array_values(array_unique($routeRefMatches[1] ?? []));

    return ['layout' => $layout, 'includes' => $includes, 'components' => $components, 'view_models' => $view_models, 'ui_enums' => $ui_enums, 'route_refs' => $route_refs];
}

// Build the graph as an adjacency list: node id → {type, label, edges:[target ids]}.
$nodes = [];
$edges = [];

$addNode = function (string $id, string $type, string $label) use (&$nodes): void {
    $nodes[$id] ??= ['id' => $id, 'type' => $type, 'label' => $label];
};

$addEdge = function (string $from, string $to, string $rel) use (&$edges): void {
    $edges[] = ['from' => $from, 'to' => $to, 'rel' => $rel];
};

// Register layout and component nodes.
$addNode('layout:base', 'layout', 'base');
foreach (Component::cases() as $Component) {
    $addNode('component:' . $Component->value, 'component', $Component->value);
}

// Scan Table classes (models) — each carries its own schema via #[Column] attributes.
$tablesDir = $projectRoot . 'src/Tables';
foreach (glob($tablesDir . '/*.php') ?: [] as $tableFile) {
    $className = basename($tableFile, '.php');
    $fqcn      = 'ZeroToProd\\Thryds\\Tables\\' . $className;
    if (! class_exists($fqcn)) {
        continue;
    }
    $ref        = new ReflectionClass($fqcn);
    $tableAttrs = $ref->getAttributes(Table::class);
    if ($tableAttrs === []) {
        continue;
    }
    $addNode('model:' . $className, 'model', $className);
}

// Walk each route.
foreach (Route::cases() as $Route) {
    $routeId = 'route:' . $Route->name;
    $addNode($routeId, 'route', $Route->value . ($Route->isDevOnly() ? ' [dev]' : ''));
    $nodes[$routeId]['methods']  = array_map(fn(RouteOperation $op): string => $op->HttpMethod->value, $Route->operations());
    $nodes[$routeId]['dev_only'] = $Route->isDevOnly();

    $controller = $explicitControllers[$Route->name] ?? null;
    $view       = View::tryFrom($Route->name);

    if ($controller !== null) {
        $nodes[$routeId]['registration'] = 'explicit';
        $controllerId = 'controller:' . $controller;
        $addNode($controllerId, 'controller', $controller);
        $addEdge($routeId, $controllerId, 'handled_by');

        if ($view !== null) {
            $viewId = 'view:' . $view->value;
            $addNode($viewId, 'view', $view->value);
            $addEdge($controllerId, $viewId, 'renders');
        }
    } elseif ($view !== null) {
        // Convention-registered: route renders the view directly.
        $nodes[$routeId]['registration'] = 'convention';
        $viewId = 'view:' . $view->value;
        $addNode($viewId, 'view', $view->value);
        $addEdge($routeId, $viewId, 'renders');
    } else {
        // No view match — JSON endpoint or similar.
        $nodes[$routeId]['registration'] = 'none';
    }
}

// Wire persists and redirects_to edges from controllers via attributes.
foreach ($explicitControllers as $controllerName) {
    $fqcn = 'ZeroToProd\\Thryds\\Controllers\\' . $controllerName;
    if (! class_exists($fqcn)) {
        continue;
    }
    $ref = new ReflectionClass($fqcn);
    foreach ($ref->getAttributes(Persists::class) as $attr) {
        $modelFqcn = $attr->newInstance()->model;
        $shortName = substr(strrchr($modelFqcn, '\\') ?: ('\\' . $modelFqcn), 1);
        $modelId   = 'model:' . $shortName;
        if (isset($nodes[$modelId])) {
            $addEdge('controller:' . $controllerName, $modelId, 'persists');
        }
    }
    foreach ($ref->getAttributes(RedirectsTo::class) as $attr) {
        $route   = $attr->newInstance()->Route;
        $routeId = 'route:' . $route->name;
        if (isset($nodes[$routeId])) {
            $addEdge('controller:' . $controllerName, $routeId, 'redirects_to');
        }
    }
}

// Walk each view template, connecting to layout / components.
foreach (View::cases() as $View) {
    $templatePath = $templatesDir . '/' . $View->value . '.blade.php';
    if (! file_exists($templatePath)) {
        continue;
    }

    $viewId = 'view:' . $View->value;
    $addNode($viewId, 'view', $View->value);
    $deps   = parseTemplate($templatePath);

    if ($deps['layout'] !== null) {
        $layoutId = 'layout:' . $deps['layout'];
        $addNode($layoutId, 'layout', $deps['layout']);
        $addEdge($viewId, $layoutId, 'extends');
    }

    foreach ($deps['includes'] as $include) {
        $includeId = 'view:' . $include;
        $addNode($includeId, 'view', $include);
        $addEdge($viewId, $includeId, 'includes');
    }

    foreach ($deps['components'] as $component) {
        if (in_array($component, $componentNames, true)) {
            $addEdge($viewId, 'component:' . $component, 'uses');
        }
    }

    foreach ($deps['route_refs'] as $routeName) {
        if (in_array($routeName, $routeNames, true)) {
            $addEdge($viewId, 'route:' . $routeName, 'references');
        }
    }

    foreach ($deps['view_models'] as $viewModel) {
        $addNode('viewmodel:' . $viewModel, 'viewmodel', $viewModel);
        $addEdge($viewId, 'viewmodel:' . $viewModel, 'receives');
    }
}

// Walk each component template, connecting to UI enums and extracting props.
foreach (Component::cases() as $Component) {
    $templatePath = $templatesDir . '/components/' . $Component->value . '.blade.php';
    if (! file_exists($templatePath)) {
        continue;
    }

    $componentId    = 'component:' . $Component->value;
    $templateSource = file_get_contents($templatePath);
    $deps           = parseTemplate($templatePath);

    $nodes[$componentId]['props'] = parseComponentProps($templateSource);

    foreach ($deps['ui_enums'] as $uiEnum) {
        $addNode('ui_enum:' . $uiEnum, 'ui_enum', $uiEnum);
        $addEdge($componentId, 'ui_enum:' . $uiEnum, 'uses_enum');
    }
}

/**
 * Extract the human-readable description from a PHP docblock string.
 *
 * Returns the first non-empty, non-tag line found in the block body.
 * Returns an empty string when no docblock exists or only tag lines are present.
 */
function docblockDescription(string|false $doc): string
{
    if ($doc === false) {
        return '';
    }
    foreach (preg_split('/\r?\n/', $doc) as $line) {
        $trimmed = rtrim(trim(ltrim($line, " \t/*")), " \t*/");
        if ($trimmed !== '' && $trimmed[0] !== '@') {
            return $trimmed;
        }
    }
    return '';
}

/** Returns the 1-based line number of `case <name>` in a PHP enum source file. */
function findCaseLine(string $filePath, string $caseName): ?int
{
    foreach (file($filePath) as $i => $line) {
        if (preg_match('/^\s*case\s+' . preg_quote($caseName, '/') . '\s*[=;]/', $line)) {
            return $i + 1;
        }
    }
    return null;
}

/**
 * Append description and source file paths to a node based on its type and label.
 *
 * Paths are relative to the project root. Template sources include `"missing": true`
 * when the file does not exist — a missing template is a detectable gap, not an error.
 *
 * @param array<string,string> $routeDescriptions Route case name → description string.
 */
function decorateNode(array $node, string $projectRoot, string $templatesDir, array $routeDescriptions): array
{
    $relPath = static fn(string $abs): string => str_replace($projectRoot, '', $abs);

    $source = static function (string $role, string $abs, ?int $line = null) use ($relPath): array {
        $entry = ['role' => $role, 'path' => $relPath($abs)];
        if ($line !== null) {
            $entry['line'] = $line;
        }
        if (! file_exists($abs)) {
            $entry['missing'] = true;
        }
        return $entry;
    };

    $label = $node['label'];

    switch ($node['type']) {
        case 'route':
            $caseName  = substr($node['id'], strlen('route:'));
            $routeFile = $projectRoot . 'src/Routes/Route.php';
            $node['description'] = $routeDescriptions[$caseName] ?? '';
            $node['sources']     = [
                $source('definition', $routeFile, findCaseLine($routeFile, $caseName)),
            ];
            break;

        case 'view':
            $templateAbs = $templatesDir . '/' . $label . '.blade.php';
            $sources     = [$source('template', $templateAbs)];
            $viewFile    = $projectRoot . 'src/Blade/View.php';
            $line        = findCaseLine($viewFile, $label);
            if ($line !== null) {
                $sources[] = $source('definition', $viewFile, $line);
            }
            $node['description'] = $routeDescriptions[$label] ?? '';
            $node['sources']     = $sources;
            break;

        case 'component':
            $description   = '';
            $templateAbs   = $templatesDir . '/components/' . $label . '.blade.php';
            $sources       = [$source('template', $templateAbs)];
            $componentFile = $projectRoot . 'src/Blade/Component.php';
            foreach (Component::cases() as $case) {
                if ($case->value === $label) {
                    $description = docblockDescription(
                        new ReflectionEnumUnitCase(Component::class, $case->name)->getDocComment(),
                    );
                    $sources[] = $source('definition', $componentFile, findCaseLine($componentFile, $case->name));
                    break;
                }
            }
            $node['description'] = $description;
            $node['props']     ??= [];
            $node['sources']     = $sources;
            break;

        case 'controller':
            $fqcn = 'ZeroToProd\\Thryds\\Controllers\\' . $label;
            $node['description'] = class_exists($fqcn)
                ? docblockDescription(new ReflectionClass($fqcn)->getDocComment())
                : '';
            $node['sources'] = [
                $source('class', $projectRoot . 'src/Controllers/' . $label . '.php'),
            ];
            break;

        case 'layout':
            $node['description'] = 'Base HTML layout';
            $node['sources']     = [
                $source('template', $templatesDir . '/' . $label . '.blade.php'),
            ];
            break;

        case 'viewmodel':
            $fqcn = 'ZeroToProd\\Thryds\\ViewModels\\' . $label;
            if (class_exists($fqcn)) {
                $ref                 = new ReflectionClass($fqcn);
                $node['description'] = docblockDescription($ref->getDocComment());
                $viewKey             = $ref->getConstant('view_key');
                $node['view_key']    = $viewKey !== false ? $viewKey : '';
                $fields              = [];
                foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                    if (! $prop->isStatic() && $prop->getDeclaringClass()->getName() === $fqcn) {
                        $fields[] = ['name' => $prop->getName(), 'type' => $prop->getType()?->getName() ?? ''];
                    }
                }
                $node['fields'] = $fields;
            } else {
                $node['description'] = '';
                $node['view_key']    = '';
                $node['fields']      = [];
            }
            $node['sources'] = [
                $source('class', $projectRoot . 'src/ViewModels/' . $label . '.php'),
            ];
            break;

        case 'ui_enum':
            $fqcn = 'ZeroToProd\\Thryds\\UI\\' . $label;
            if (enum_exists($fqcn)) {
                $ref                 = new ReflectionEnum($fqcn);
                $node['description'] = docblockDescription($ref->getDocComment());
                $node['cases']       = array_map(fn($case) => $case->getBackingValue(), $ref->getCases());
            } else {
                $node['description'] = '';
                $node['cases']       = [];
            }
            $node['sources'] = [
                $source('definition', $projectRoot . 'src/UI/' . $label . '.php'),
            ];
            break;

        case 'model':
            $fqcn = 'ZeroToProd\\Thryds\\Tables\\' . $label;
            if (class_exists($fqcn)) {
                $ref                 = new ReflectionClass($fqcn);
                $node['description'] = docblockDescription($ref->getDocComment());
                $tableAttr           = $ref->getAttributes(Table::class)[0] ?? null;
                $node['table_name']  = $tableAttr ? $tableAttr->newInstance()->TableName->value : '';
                $fields              = [];
                foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                    if ($prop->isStatic()) {
                        continue;
                    }
                    $colAttrs = $prop->getAttributes(Column::class);
                    if ($colAttrs === []) {
                        continue;
                    }
                    $col      = $colAttrs[0]->newInstance();
                    $fields[] = [
                        'name'     => $prop->getName(),
                        'type'     => $col->DataType->value,
                        'nullable' => $col->nullable,
                        'comment'  => $col->comment,
                    ];
                }
                $node['fields'] = $fields;
            } else {
                $node['description'] = '';
                $node['table_name']  = '';
                $node['fields']      = [];
            }
            $node['sources'] = [
                $source('class', $projectRoot . 'src/Tables/' . $label . '.php'),
            ];
            break;

        case 'test':
            $node['sources'] = [
                $source('class', $projectRoot . 'tests/Integration/' . $label . '.php'),
            ];
            break;
    }

    return $node;
}

// Scan Integration test files and wire covers edges to routes and controllers.
$testsDir = $projectRoot . 'tests/Integration';
foreach (glob($testsDir . '/*Test.php') ?: [] as $testFile) {
    $className = basename($testFile, '.php');
    $testId    = 'test:' . $className;
    $addNode($testId, 'test', $className);

    // Convention: strip Test suffix — if result is a known controller, it covers it.
    $bare = substr($className, 0, -4); // remove 'Test'
    if (in_array($bare, $explicitControllers, true)) {
        $controllerId = 'controller:' . $bare;
        if (isset($nodes[$controllerId])) {
            $addEdge($testId, $controllerId, 'covers');
        }
    }

    // Parse Route::caseName references in the file body.
    $fileSource = file_get_contents($testFile);
    preg_match_all('/\bRoute::(\w+)\b/', $fileSource, $routeRefMatches);
    foreach (array_unique($routeRefMatches[1] ?? []) as $routeName) {
        if (in_array($routeName, $routeNames, true)) {
            $addEdge($testId, 'route:' . $routeName, 'covers');
        }
    }
}

$routeDescriptions = [];
foreach (Route::cases() as $Route) {
    $routeDescriptions[$Route->name] = $Route->description();
}

$decoratedNodes = array_map(
    fn(array $node): array => decorateNode($node, $projectRoot, $templatesDir, $routeDescriptions),
    array_values($nodes),
);

/** Read #[ClosedSet] addCase instructions from closed-set enums exposed in the graph. */
$extensionGuides = [];
foreach ([
    'route'     => Route::class,
    'view'      => View::class,
    'component' => Component::class,
] as $key => $enumClass) {
    $attrs = new ReflectionEnum($enumClass)->getAttributes(ClosedSet::class);
    if ($attrs !== []) {
        $extensionGuides[$key] = $attrs[0]->newInstance()->addCase;
    }
}
$extensionGuides['model']     = Table::addCase;
$extensionGuides['viewmodel'] = ViewModel::addCase;

if ($format === 'dot') {
    // Node shapes by type for visual distinction.
    $shapes = [
        'route'      => 'ellipse',
        'controller' => 'box',
        'view'       => 'note',
        'component'  => 'component',
        'layout'     => 'tab',
        'viewmodel'  => 'cylinder',
        'ui_enum'    => 'diamond',
        'model'      => 'cylinder',
        'test'       => 'hexagon',
    ];
    $colors = [
        'route'      => '#AED6F1',
        'controller' => '#A9DFBF',
        'view'       => '#F9E79F',
        'component'  => '#F5CBA7',
        'layout'     => '#D7BDE2',
        'viewmodel'  => '#FADBD8',
        'ui_enum'    => '#D5F5E3',
        'model'      => '#D5DBDB',
        'test'       => '#D6EAF8',
    ];

    $dot = "digraph inventory {\n";
    $dot .= "    rankdir=LR;\n";
    $dot .= "    node [fontname=\"Helvetica\", fontsize=11];\n";
    $dot .= "    edge [fontsize=9];\n\n";

    foreach ($decoratedNodes as $node) {
        $shape    = $shapes[$node['type']] ?? 'ellipse';
        $color    = $colors[$node['type']] ?? '#FFFFFF';
        $label    = addslashes($node['label']);
        $urlAttr  = isset($node['sources'][0]) ? ", URL=\"{$node['sources'][0]['path']}\"" : '';
        $dot .= "    \"{$node['id']}\" [label=\"{$label}\", shape={$shape}, style=filled, fillcolor=\"{$color}\"{$urlAttr}];\n";
    }

    $dot .= "\n";

    foreach ($edges as $edge) {
        $dot .= "    \"{$edge['from']}\" -> \"{$edge['to']}\" [label=\"{$edge['rel']}\"];\n";
    }

    $dot .= "}\n";
    echo $dot;
} else {
    echo json_encode(
        [
            'nodes'            => $decoratedNodes,
            'edges'            => $edges,
            'extension_guides' => $extensionGuides,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
    ) . "\n";
}
