<?php

declare(strict_types=1);

/**
 * Generate a dependency graph of routes → controllers → views → components/layouts.
 *
 * Outputs DOT (Graphviz), JSON, or YAML based on --format= argument.
 *
 * Usage: docker compose exec web php scripts/inventory.php [--format=dot|json|yaml]
 * Via Composer: ./run list:inventory [-- --format=dot]
 *
 * Exit 0 on success.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$inventoryConfig = Yaml::parseFile(__DIR__ . '/inventory-config.yaml');

// Resolve attribute classes from config.
$HandlesRoute      = $inventoryConfig['attributes']['handles_route'];
$CoversRoute       = $inventoryConfig['attributes']['covers_route'];
$ExtendsLayout     = $inventoryConfig['attributes']['extends_layout'];
$PageTitle         = $inventoryConfig['attributes']['page_title'];
$ReceivesViewModel = $inventoryConfig['attributes']['receives_viewmodel'];
$RendersView       = $inventoryConfig['attributes']['renders_view'];
$UsesComponent     = $inventoryConfig['attributes']['uses_component'];
$ViewModelAttr     = $inventoryConfig['attributes']['view_model'];
$TableAttr         = $inventoryConfig['attributes']['table'];
$ColumnAttr        = $inventoryConfig['attributes']['column'];
$PrimaryKeyAttr    = $inventoryConfig['attributes']['primary_key'];
$IndexAttr         = $inventoryConfig['attributes']['index'];
$Persists          = $inventoryConfig['attributes']['persists'];
$RedirectsTo       = $inventoryConfig['attributes']['redirects_to'];
$RouteOperation    = $inventoryConfig['attributes']['route_operation'];
$Prop              = $inventoryConfig['attributes']['prop'];
$ClosedSet         = $inventoryConfig['attributes']['closed_set'];

// Resolve enum classes from config.
$Route     = $inventoryConfig['enums']['route'];
$View      = $inventoryConfig['enums']['view'];
$Component = $inventoryConfig['enums']['component'];

// Resolve namespaces and paths from config.
$controllersNamespace     = $inventoryConfig['namespaces']['controllers'];
$tablesNamespace          = $inventoryConfig['namespaces']['tables'];
$viewmodelsNamespace      = $inventoryConfig['namespaces']['viewmodels'];
$uiNamespace              = $inventoryConfig['namespaces']['ui'];
$testsIntegrationNamespace = $inventoryConfig['namespaces']['tests_integration'];
$sourcePaths              = $inventoryConfig['source_paths'];

// Parse --format= argument; default to json.
$format = 'json';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, strlen('--format='));
    }
}
if (! in_array($format, ['json', 'dot', 'yaml'], true)) {
    fwrite(STDERR, "Unknown format: $format. Use json, dot, or yaml.\n");
    exit(1);
}

$projectRoot  = realpath(__DIR__ . '/../') . '/';
$templatesDir = $projectRoot . $inventoryConfig['template_dir'];

/** Controllers discovered via #[HandlesRoute] attribute (route name → short class name). */
$explicitControllers = [];
$controllersDir = $projectRoot . $inventoryConfig['controllers_dir'];
foreach (glob($controllersDir . '/*.php') ?: [] as $controllerFile) {
    $controllerClassName = basename($controllerFile, '.php');
    $controllerFqcn = $controllersNamespace . '\\' . $controllerClassName;
    if (!class_exists($controllerFqcn)) {
        continue;
    }
    $controllerRef = new ReflectionClass($controllerFqcn);
    $handlesRouteAttrs = $controllerRef->getAttributes($HandlesRoute);
    if ($handlesRouteAttrs !== []) {
        $handledRoute = $handlesRouteAttrs[0]->newInstance()->Route;
        $explicitControllers[$handledRoute->name] = $controllerClassName;
    }
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
foreach ($Component::cases() as $componentCase) {
    $addNode('component:' . $componentCase->value, 'component', $componentCase->value);
}

// Scan Table classes (models) — each carries its own schema via #[Column] attributes.
$tablesDir = $projectRoot . $inventoryConfig['tables_dir'];
foreach (glob($tablesDir . '/*.php') ?: [] as $tableFile) {
    $className = basename($tableFile, '.php');
    $fqcn      = $tablesNamespace . '\\' . $className;
    if (! class_exists($fqcn)) {
        continue;
    }
    $ref        = new ReflectionClass($fqcn);
    $tableAttrs = $ref->getAttributes($TableAttr);
    if ($tableAttrs === []) {
        continue;
    }
    $addNode('model:' . $className, 'model', $className);
}

// Walk each route.
foreach ($Route::cases() as $routeCase) {
    $routeId = 'route:' . $routeCase->name;
    $addNode($routeId, 'route', $routeCase->value . ($routeCase->isDevOnly() ? ' [dev]' : ''));
    $nodes[$routeId]['methods']  = array_map(fn($op): string => $op->HttpMethod->value, $routeCase->operations());
    $nodes[$routeId]['dev_only'] = $routeCase->isDevOnly();

    $controller = $explicitControllers[$routeCase->name] ?? null;
    $view       = $routeCase->rendersView();

    if ($controller !== null) {
        $nodes[$routeId]['registration'] = 'explicit';
        $controllerId = 'controller:' . $controller;
        $addNode($controllerId, 'controller', $controller);
        $addEdge($routeId, $controllerId, 'handled_by');

        // Controller→View edge comes from #[RendersView] on the controller class.
        $controllerFqcn = $controllersNamespace . '\\' . $controller;
        if (class_exists($controllerFqcn)) {
            $rvAttrs = new ReflectionClass($controllerFqcn)->getAttributes($RendersView);
            if ($rvAttrs !== []) {
                $controllerView = $rvAttrs[0]->newInstance()->View;
                $viewId = 'view:' . $controllerView->value;
                $addNode($viewId, 'view', $controllerView->value);
                $addEdge($controllerId, $viewId, 'renders');
            }
        }
    } elseif ($view !== null) {
        // View-only route: #[RendersView] on the Route case.
        $nodes[$routeId]['registration'] = 'attribute';
        $viewId = 'view:' . $view->value;
        $addNode($viewId, 'view', $view->value);
        $addEdge($routeId, $viewId, 'renders');
    } else {
        // No view — JSON endpoint or similar.
        $nodes[$routeId]['registration'] = 'none';
    }
}

// Wire persists and redirects_to edges from controllers via attributes.
foreach ($explicitControllers as $controllerName) {
    $fqcn = $controllersNamespace . '\\' . $controllerName;
    if (! class_exists($fqcn)) {
        continue;
    }
    $ref = new ReflectionClass($fqcn);
    foreach ($ref->getAttributes($Persists) as $attr) {
        $modelFqcn = $attr->newInstance()->model;
        $shortName = substr(strrchr($modelFqcn, '\\') ?: ('\\' . $modelFqcn), 1);
        $modelId   = 'model:' . $shortName;
        if (isset($nodes[$modelId])) {
            $addEdge('controller:' . $controllerName, $modelId, 'persists');
        }
    }
    foreach ($ref->getAttributes($RedirectsTo) as $attr) {
        $route   = $attr->newInstance()->Route;
        $routeId = 'route:' . $route->name;
        if (isset($nodes[$routeId])) {
            $addEdge('controller:' . $controllerName, $routeId, 'redirects_to');
        }
    }
}

// Walk each View enum case — relationships come from attributes, not templates.
foreach ($View::cases() as $viewCase) {
    $viewId = 'view:' . $viewCase->value;
    $addNode($viewId, 'view', $viewCase->value);

    $ref = new ReflectionEnumUnitCase($View, $viewCase->name);

    $layoutAttrs = $ref->getAttributes($ExtendsLayout);
    if ($layoutAttrs !== []) {
        $layoutName = $layoutAttrs[0]->newInstance()->layout;
        $layoutId   = 'layout:' . $layoutName;
        $addNode($layoutId, 'layout', $layoutName);
        $addEdge($viewId, $layoutId, 'extends');
    }

    $componentAttrs = $ref->getAttributes($UsesComponent);
    if ($componentAttrs !== []) {
        foreach ($componentAttrs[0]->newInstance()->components as $comp) {
            $addEdge($viewId, 'component:' . $comp->value, 'uses');
        }
    }

    $viewModelAttrs = $ref->getAttributes($ReceivesViewModel);
    if ($viewModelAttrs !== []) {
        foreach ($viewModelAttrs[0]->newInstance()->view_models as $vmClass) {
            $shortName = substr(strrchr($vmClass, '\\') ?: ('\\' . $vmClass), 1);
            $addNode('viewmodel:' . $shortName, 'viewmodel', $shortName);
            $addEdge($viewId, 'viewmodel:' . $shortName, 'receives');
        }
    }
}

// Walk each Component enum case — props come from #[Prop] attributes, not @props().
foreach ($Component::cases() as $componentCase) {
    $componentId = 'component:' . $componentCase->value;
    $addNode($componentId, 'component', $componentCase->value);

    $ref      = new ReflectionEnumUnitCase($Component, $componentCase->name);
    $propAttrs = $ref->getAttributes($Prop, ReflectionAttribute::IS_INSTANCEOF);
    $props     = [];
    foreach ($propAttrs as $attr) {
        $prop      = $attr->newInstance();
        $enumShort = $prop->enum !== null
            ? substr(strrchr($prop->enum, '\\') ?: ('\\' . $prop->enum), 1)
            : null;
        $props[] = ['name' => $prop->Props->value, 'default' => $prop->default, 'enum' => $enumShort];

        if ($enumShort !== null) {
            $addNode('ui_enum:' . $enumShort, 'ui_enum', $enumShort);
            $addEdge($componentId, 'ui_enum:' . $enumShort, 'uses_enum');
        }
    }
    $nodes[$componentId]['props'] = $props;
}

$addNode('ui_enum:Layout', 'ui_enum', 'Layout');
$addNode('ui_enum:Props', 'ui_enum', 'Props');

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
function decorateNode(array $node, string $projectRoot, string $templatesDir, array $routeDescriptions, array $inventoryConfig): array
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
            $routeFile = $projectRoot . $inventoryConfig['source_paths']['route'];
            $node['description'] = $routeDescriptions[$caseName] ?? '';
            $node['sources']     = [
                $source('definition', $routeFile, findCaseLine($routeFile, $caseName)),
            ];
            break;

        case 'view':
            $templateAbs = $templatesDir . '/' . $label . '.blade.php';
            $sources     = [$source('template', $templateAbs)];
            $viewFile    = $projectRoot . $inventoryConfig['source_paths']['view'];
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
            $componentClass = $inventoryConfig['enums']['component'];
            $componentFile = $projectRoot . $inventoryConfig['source_paths']['component'];
            foreach ($componentClass::cases() as $case) {
                if ($case->value === $label) {
                    $description = docblockDescription(
                        new ReflectionEnumUnitCase($componentClass, $case->name)->getDocComment(),
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
            $fqcn = $inventoryConfig['namespaces']['controllers'] . '\\' . $label;
            $node['description'] = class_exists($fqcn)
                ? docblockDescription(new ReflectionClass($fqcn)->getDocComment())
                : '';
            $node['sources'] = [
                $source('class', $projectRoot . $inventoryConfig['source_paths']['controllers'] . '/' . $label . '.php'),
            ];
            break;

        case 'layout':
            $node['description'] = 'Base HTML layout';
            $node['sources']     = [
                $source('template', $templatesDir . '/' . $label . '.blade.php'),
            ];
            break;

        case 'viewmodel':
            $fqcn = $inventoryConfig['namespaces']['viewmodels'] . '\\' . $label;
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
                $source('class', $projectRoot . $inventoryConfig['source_paths']['viewmodels'] . '/' . $label . '.php'),
            ];
            break;

        case 'ui_enum':
            $fqcn = $inventoryConfig['namespaces']['ui'] . '\\' . $label;
            if (enum_exists($fqcn)) {
                $ref                 = new ReflectionEnum($fqcn);
                $node['description'] = docblockDescription($ref->getDocComment());
                $node['cases']       = array_map(fn($case) => $case->getBackingValue(), $ref->getCases());
            } else {
                $node['description'] = '';
                $node['cases']       = [];
            }
            $node['sources'] = [
                $source('definition', $projectRoot . $inventoryConfig['source_paths']['ui'] . '/' . $label . '.php'),
            ];
            break;

        case 'model':
            $fqcn = $inventoryConfig['namespaces']['tables'] . '\\' . $label;
            if (class_exists($fqcn)) {
                $ref                 = new ReflectionClass($fqcn);
                $node['description'] = docblockDescription($ref->getDocComment());
                $tableAttr           = $ref->getAttributes($inventoryConfig['attributes']['table'])[0] ?? null;
                $node['table_name']  = $tableAttr ? $tableAttr->newInstance()->TableName->value : '';
                $fields              = [];
                foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                    if ($prop->isStatic()) {
                        continue;
                    }
                    $colAttrs = $prop->getAttributes($inventoryConfig['attributes']['column']);
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
                $source('class', $projectRoot . $inventoryConfig['source_paths']['tables'] . '/' . $label . '.php'),
            ];
            break;

        case 'test':
            $node['sources'] = [
                $source('class', $projectRoot . $inventoryConfig['source_paths']['tests_integration'] . '/' . $label . '.php'),
            ];
            break;
    }

    // Bubble up a top-level missing flag when any declared source file is absent.
    foreach ($node['sources'] ?? [] as $src) {
        if ($src['missing'] ?? false) {
            $node['missing'] = true;
            break;
        }
    }

    return $node;
}

// Walk each integration test — coverage comes from #[CoversRoute], not regex.
$testsDir = $projectRoot . $inventoryConfig['tests_dir'];
foreach (glob($testsDir . '/*Test.php') ?: [] as $testFile) {
    $className = basename($testFile, '.php');
    $fqcn      = $testsIntegrationNamespace . '\\' . $className;
    if (! class_exists($fqcn)) {
        continue;
    }

    $testId = 'test:' . $className;
    $addNode($testId, 'test', $className);

    $ref        = new ReflectionClass($fqcn);
    $coversAttrs = $ref->getAttributes($CoversRoute);
    if ($coversAttrs !== []) {
        foreach ($coversAttrs[0]->newInstance()->routes as $route) {
            $addEdge($testId, 'route:' . $route->name, 'covers');
        }
    }

    // Convention: if test name matches a controller, wire that edge too.
    $bare = substr($className, 0, -4); // remove 'Test'
    if (in_array($bare, $explicitControllers, true)) {
        $addEdge($testId, 'controller:' . $bare, 'covers');
    }
}

$routeDescriptions = [];
foreach ($Route::cases() as $routeCase) {
    $routeDescriptions[$routeCase->name] = $routeCase->description();
}

$decoratedNodes = array_map(
    fn(array $node): array => decorateNode($node, $projectRoot, $templatesDir, $routeDescriptions, $inventoryConfig),
    array_values($nodes),
);

/** Read #[ClosedSet] addCase instructions from closed-set enums exposed in the graph. */
$extensionGuides = [];
foreach ([
    'route'     => $Route,
    'view'      => $View,
    'component' => $Component,
] as $key => $enumClass) {
    $attrs = new ReflectionEnum($enumClass)->getAttributes($ClosedSet);
    if ($attrs !== []) {
        $extensionGuides[$key] = $attrs[0]->newInstance()->addCase;
    }
}
$extensionGuides['model']      = $TableAttr::addCase;
$extensionGuides['viewmodel']  = $ViewModelAttr::addCase;
$extensionGuides['controller'] = implode("\n", [
    '1. Add entry to thryds.yaml controllers section.',
    '2. Run ./run sync:manifest.',
    '3. Implement controller logic.',
    '4. Run ./run fix:all.',
]);

/**
 * Transform the flat node/edge graph into the grouped YAML manifest matching the thryds.yaml schema.
 *
 * @param array<int, array<string, mixed>> $decoratedNodes
 * @param array<int, array{from: string, to: string, rel: string}> $edges
 */
function buildYamlManifest(array $decoratedNodes, array $edges, array $inventoryConfig): string
{
    $edgesFrom = [];
    foreach ($edges as $e) {
        $edgesFrom[$e['from']][] = $e;
    }

    $nodeById = [];
    foreach ($decoratedNodes as $n) {
        $nodeById[$n['id']] = $n;
    }

    $componentClass = $inventoryConfig['enums']['component'];
    $compValueToName = [];
    foreach ($componentClass::cases() as $c) {
        $compValueToName[$c->value] = $c->name;
    }

    $yaml = "# thryds.yaml — project structure manifest\n";
    $yaml .= "# Enforcement: ./run check:manifest (diff against attribute graph)\n";
    $yaml .= "# Sync: ./run sync:manifest (scaffold missing code)\n";

    // === Routes ===
    $routeClass = $inventoryConfig['enums']['route'];
    $routeData = [];
    foreach ($routeClass::cases() as $routeCase) {
        $routeId = 'route:' . $routeCase->name;
        $entry = [];
        $entry['path'] = $routeCase->value;
        $entry['description'] = $routeCase->description();
        $entry['dev_only'] = $routeCase->isDevOnly();
        $ops = [];
        foreach ($routeCase->operations() as $op) {
            $ops[$op->HttpMethod->value] = $op->description;
        }
        $entry['operations'] = $ops;

        foreach ($edgesFrom[$routeId] ?? [] as $edge) {
            if ($edge['rel'] === 'handled_by') {
                $entry['controller'] = substr($edge['to'], strlen('controller:'));
            }
        }

        $viewName = null;
        foreach ($edgesFrom[$routeId] ?? [] as $edge) {
            if ($edge['rel'] === 'renders') {
                $viewName = substr($edge['to'], strlen('view:'));
            }
        }
        if ($viewName === null && isset($entry['controller'])) {
            foreach ($edgesFrom['controller:' . $entry['controller']] ?? [] as $edge) {
                if ($edge['rel'] === 'renders') {
                    $viewName = substr($edge['to'], strlen('view:'));
                }
            }
        }
        if ($viewName !== null) {
            $entry['view'] = $viewName;
        }

        $routeData[$routeCase->name] = $entry;
    }
    ksort($routeData);
    $yaml .= "\n" . Yaml::dump(['routes' => $routeData], 4, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

    // === Controllers ===
    $controllerData = [];
    foreach ($decoratedNodes as $n) {
        if ($n['type'] !== 'controller') {
            continue;
        }
        // Skip controllers with missing source files — they belong in desired state only.
        if ($n['missing'] ?? false) {
            continue;
        }
        $name = $n['label'];
        $controllerId = 'controller:' . $name;
        $entry = [];

        // Find which route this controller handles
        foreach ($edges as $e) {
            if ($e['to'] === $controllerId && $e['rel'] === 'handled_by') {
                $routeCaseName = substr($e['from'], strlen('route:'));
                $entry['route'] = $routeCaseName;
                $routeEnum = null;
                foreach ($routeClass::cases() as $r) {
                    if ($r->name === $routeCaseName) {
                        $routeEnum = $r;
                        break;
                    }
                }
                if ($routeEnum !== null) {
                    $ops = [];
                    foreach ($routeEnum->operations() as $op) {
                        $ops[$op->HttpMethod->value] = $op->description;
                    }
                    $entry['operations'] = $ops;
                }
                break;
            }
        }

        $renders = null;
        $persists = [];
        $redirectsTo = [];
        foreach ($edgesFrom[$controllerId] ?? [] as $edge) {
            match ($edge['rel']) {
                'renders' => $renders = substr($edge['to'], strlen('view:')),
                'persists' => $persists[] = substr($edge['to'], strlen('model:')),
                'redirects_to' => $redirectsTo[] = substr($edge['to'], strlen('route:')),
                default => null,
            };
        }
        if ($renders !== null) {
            $entry['renders'] = $renders;
        }
        $entry['persists'] = $persists;
        $entry['redirects_to'] = $redirectsTo;

        $controllerData[$name] = $entry;
    }
    ksort($controllerData);
    $yaml .= "\n" . Yaml::dump(['controllers' => $controllerData], 4, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

    // === Views ===
    $viewClass = $inventoryConfig['enums']['view'];
    $extendsLayoutAttr = $inventoryConfig['attributes']['extends_layout'];
    $pageTitleAttr = $inventoryConfig['attributes']['page_title'];
    $viewData = [];
    foreach ($viewClass::cases() as $viewCase) {
        $viewId = 'view:' . $viewCase->value;
        $entry = [];

        $ref = new \ReflectionEnumUnitCase($viewClass, $viewCase->name);

        $layoutAttrs = $ref->getAttributes($extendsLayoutAttr);
        $entry['layout'] = $layoutAttrs !== [] ? $layoutAttrs[0]->newInstance()->layout : '';

        $titleAttrs = $ref->getAttributes($pageTitleAttr);
        $entry['title'] = $titleAttrs !== [] ? $titleAttrs[0]->newInstance()->title : '';

        $components = [];
        foreach ($edgesFrom[$viewId] ?? [] as $edge) {
            if ($edge['rel'] === 'uses') {
                $compValue = substr($edge['to'], strlen('component:'));
                $components[] = $compValueToName[$compValue] ?? $compValue;
            }
        }
        $entry['components'] = $components;

        $viewmodels = [];
        foreach ($edgesFrom[$viewId] ?? [] as $edge) {
            if ($edge['rel'] === 'receives') {
                $viewmodels[] = substr($edge['to'], strlen('viewmodel:'));
            }
        }
        $entry['viewmodels'] = $viewmodels;

        $viewData[$viewCase->value] = $entry;
    }
    ksort($viewData);
    $yaml .= "\n" . Yaml::dump(['views' => $viewData], 3, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

    // === Components ===
    $componentData = [];
    foreach ($componentClass::cases() as $componentCase) {
        $componentId = 'component:' . $componentCase->value;
        $node = $nodeById[$componentId] ?? null;
        $entry = [];
        $entry['description'] = $node['description'] ?? '';
        $props = [];
        foreach ($node['props'] ?? [] as $prop) {
            $propEntry = [];
            if ($prop['default'] !== '') {
                $propEntry['default'] = $prop['default'];
            }
            if ($prop['enum'] !== null) {
                $propEntry['enum'] = $prop['enum'];
            }
            $props[$prop['name']] = $propEntry === [] ? (object) [] : $propEntry;
        }
        $entry['props'] = $props === [] ? (object) [] : $props;
        $componentData[$componentCase->name] = $entry;
    }
    ksort($componentData);
    $yaml .= "\n" . Yaml::dump(['components' => $componentData], 4, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_OBJECT_AS_MAP);

    // === ViewModels ===
    $vmData = [];
    foreach ($decoratedNodes as $n) {
        if ($n['type'] !== 'viewmodel') {
            continue;
        }
        $entry = [];
        $entry['view_key'] = $n['view_key'] ?? '';
        $fields = [];
        foreach ($n['fields'] ?? [] as $field) {
            $fields[$field['name']] = $field['type'];
        }
        $entry['fields'] = $fields;
        $vmData[$n['label']] = $entry;
    }
    ksort($vmData);
    $yaml .= "\n" . Yaml::dump(['viewmodels' => $vmData], 4, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

    // === Enums ===
    $enumData = [];
    foreach ($decoratedNodes as $n) {
        if ($n['type'] !== 'ui_enum') {
            continue;
        }
        $enumData[$n['label']] = ['cases' => $n['cases'] ?? []];
    }
    ksort($enumData);
    $yaml .= "\n" . Yaml::dump(['enums' => $enumData], 3, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

    // === Tables ===
    $tableData = [];
    foreach ($decoratedNodes as $n) {
        if ($n['type'] !== 'model') {
            continue;
        }
        $label = $n['label'];
        $fqcn = $inventoryConfig['namespaces']['tables'] . '\\' . $label;
        $entry = [];
        $entry['table'] = $n['table_name'] ?? '';

        if (class_exists($fqcn)) {
            $ref = new \ReflectionClass($fqcn);
            $tableAttr = $ref->getAttributes($inventoryConfig['attributes']['table'])[0] ?? null;
            if ($tableAttr !== null) {
                $entry['engine'] = $tableAttr->newInstance()->Engine->value;
            }

            // Primary key
            $pkColumns = [];
            $classPk = $ref->getAttributes($inventoryConfig['attributes']['primary_key']);
            if ($classPk !== []) {
                $pkColumns = $classPk[0]->newInstance()->columns;
            } else {
                foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
                    if ($prop->isStatic()) {
                        continue;
                    }
                    $propPk = $prop->getAttributes($inventoryConfig['attributes']['primary_key']);
                    if ($propPk !== []) {
                        $cols = $propPk[0]->newInstance()->columns;
                        $pkColumns = $cols !== [] ? $cols : [$prop->getName()];
                    }
                }
            }
            $entry['primary_key'] = $pkColumns;

            // Indexes
            $indexes = [];
            foreach ($ref->getAttributes($inventoryConfig['attributes']['index'], \ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                $idx = $attr->newInstance();
                $idxEntry = ['columns' => $idx->columns];
                if ($idx->unique) {
                    $idxEntry['unique'] = true;
                }
                if ($idx->name !== '') {
                    $idxEntry['name'] = $idx->name;
                }
                $indexes[] = $idxEntry;
            }
            $entry['indexes'] = $indexes;

            // Columns with compact format
            $columns = [];
            foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
                if ($prop->isStatic()) {
                    continue;
                }
                $colAttrs = $prop->getAttributes($inventoryConfig['attributes']['column']);
                if ($colAttrs === []) {
                    continue;
                }
                $col = $colAttrs[0]->newInstance();
                $colEntry = ['type' => $col->DataType->value];
                if ($col->length !== null) {
                    $colEntry['length'] = $col->length;
                }
                if ($col->precision !== null) {
                    $colEntry['precision'] = $col->precision;
                }
                if ($col->scale !== null) {
                    $colEntry['scale'] = $col->scale;
                }
                if ($col->nullable) {
                    $colEntry['nullable'] = true;
                }
                if ($col->unsigned) {
                    $colEntry['unsigned'] = true;
                }
                if ($col->auto_increment) {
                    $colEntry['auto_increment'] = true;
                }
                if ($col->default !== null) {
                    $colEntry['default'] = $col->default;
                }
                if ($col->values !== null) {
                    $colEntry['values'] = $col->values;
                }
                $colEntry['comment'] = $col->comment;
                $columns[$prop->getName()] = $colEntry;
            }
            $entry['columns'] = $columns;
        }

        $tableData[$label] = $entry;
    }
    ksort($tableData);
    $yaml .= "\ntables:\n";
    foreach ($tableData as $name => $tbl) {
        $columns = $tbl['columns'] ?? [];
        unset($tbl['columns']);
        // Dump header fields (primary_key/indexes inline at depth 1 within entry)
        $yaml .= "\n  $name:\n";
        $yaml .= preg_replace('/^/m', '    ', trim(Yaml::dump($tbl, 1, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE))) . "\n";
        // Dump columns with inline map per column
        $yaml .= "    columns:\n";
        foreach ($columns as $colName => $colDef) {
            $yaml .= '      ' . $colName . ': ' . trim(Yaml::dump($colDef, 0, 2)) . "\n";
        }
    }

    // === Tests ===
    $testData = [];
    foreach ($decoratedNodes as $n) {
        if ($n['type'] !== 'test') {
            continue;
        }
        $testId = 'test:' . $n['label'];
        $entry = [];
        $entry['type'] = 'integration';
        $coversRoutes = [];
        foreach ($edgesFrom[$testId] ?? [] as $edge) {
            if ($edge['rel'] === 'covers' && str_starts_with($edge['to'], 'route:')) {
                $coversRoutes[] = substr($edge['to'], strlen('route:'));
            }
        }
        $entry['covers_routes'] = $coversRoutes;
        $testData[$n['label']] = $entry;
    }
    ksort($testData);
    $yaml .= "\n" . Yaml::dump(['tests' => $testData], 3, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

    return $yaml;
}

if ($format === 'yaml') {
    echo buildYamlManifest($decoratedNodes, $edges, $inventoryConfig);
} elseif ($format === 'dot') {
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
