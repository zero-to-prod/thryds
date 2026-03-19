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

$templatesDir = __DIR__ . '/../templates';

/** Controllers explicitly mapped in RouteRegistrar (route name → short class name). */
$explicitControllers = [
    'home' => 'HomeController',
];

/** All registered component names (the x- tag suffix). */
$componentNames = array_map(fn(Component $c): string => $c->value, Component::cases());

/**
 * Parse a Blade template file and return its direct dependencies.
 *
 * @return array{layout:string|null, includes:string[], components:string[]}
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

    return ['layout' => $layout, 'includes' => $includes, 'components' => $components];
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

// Walk each route.
foreach (Route::cases() as $Route) {
    $routeId = 'route:' . $Route->name;
    $addNode($routeId, 'route', $Route->value . ($Route->isDevOnly() ? ' [dev]' : ''));

    $controller = $explicitControllers[$Route->name] ?? null;
    $view       = View::tryFrom($Route->name);

    if ($controller !== null) {
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
        $viewId = 'view:' . $view->value;
        $addNode($viewId, 'view', $view->value);
        $addEdge($routeId, $viewId, 'renders');
    }
    // Routes without a matching view (e.g. JSON endpoints) emit no view edge.
}

// Walk each view template, connecting to layout / components.
foreach (View::cases() as $View) {
    $templatePath = $templatesDir . '/' . $View->value . '.blade.php';
    if (! file_exists($templatePath)) {
        continue;
    }

    $viewId = 'view:' . $View->value;
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
}

if ($format === 'dot') {
    // Node shapes by type for visual distinction.
    $shapes = [
        'route'      => 'ellipse',
        'controller' => 'box',
        'view'       => 'note',
        'component'  => 'component',
        'layout'     => 'tab',
    ];
    $colors = [
        'route'      => '#AED6F1',
        'controller' => '#A9DFBF',
        'view'       => '#F9E79F',
        'component'  => '#F5CBA7',
        'layout'     => '#D7BDE2',
    ];

    $dot = "digraph inventory {\n";
    $dot .= "    rankdir=LR;\n";
    $dot .= "    node [fontname=\"Helvetica\", fontsize=11];\n";
    $dot .= "    edge [fontsize=9];\n\n";

    foreach ($nodes as $node) {
        $shape = $shapes[$node['type']] ?? 'ellipse';
        $color = $colors[$node['type']] ?? '#FFFFFF';
        $label = addslashes($node['label']);
        $dot .= "    \"{$node['id']}\" [label=\"{$label}\", shape={$shape}, style=filled, fillcolor=\"{$color}\"];\n";
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
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
    ) . "\n";
}
