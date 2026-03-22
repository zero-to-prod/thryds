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

use Symfony\Component\Yaml\Yaml;

$projectRoot  = realpath(__DIR__ . '/../') . '/';
$manifestPath = $projectRoot . 'thryds.yaml';
$scaffold     = Yaml::parseFile(__DIR__ . '/scaffold-config.yaml');

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
            'views' => scaffoldView($name, $props, $projectRoot, $scaffold, $created),
            'components' => scaffoldComponent($name, $props, $projectRoot, $scaffold, $created),
            'viewmodels' => scaffoldViewModel($name, $props, $projectRoot, $scaffold, $created),
            'tests' => scaffoldTest($name, $props, $projectRoot, $scaffold, $created),
            'controllers' => scaffoldController($name, $props, $projectRoot, $scaffold, $created),
            'tables' => scaffoldTable($name, $props, $projectRoot, $scaffold, $created),
            'enums' => scaffoldEnum($name, $props, $projectRoot, $scaffold, $created),
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

function scaffoldView(string $name, array $props, string $root, array $scaffold, array &$created): void
{
    $templateDir  = $scaffold['directories']['templates'];
    $vmNamespace  = $scaffold['namespaces']['viewmodels'];
    $templatePath = $root . $templateDir . '/' . $name . '.blade.php';
    $files        = [];

    if (! file_exists($templatePath)) {
        $layout     = $props['layout'] ?? 'base';
        $title      = $props['title'] ?? ucfirst($name) . ' — Thryds';
        $viewModels = $props['viewmodels'] ?? [];

        $useLines = '';
        foreach ($viewModels as $vm) {
            $useLines .= "    use $vmNamespace\\$vm;\n";
        }

        $template = "@php\n{$useLines}@endphp\n@extends('$layout')\n\n@section('title', '$title')\n\n@section('body')\n    {{-- TODO: implement $name view --}}\n@endsection\n";
        file_put_contents($templatePath, $template);
        $files[] = "$templateDir/$name.blade.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'views', 'name' => $name, 'files' => $files];
    }
}

function scaffoldComponent(string $name, array $props, string $root, array $scaffold, array &$created): void
{
    $componentDir = $scaffold['directories']['components'];
    $value        = str_replace('_', '-', $name);
    $templatePath = $root . $componentDir . '/' . $value . '.blade.php';
    $files        = [];

    if (! file_exists($templatePath)) {
        $propsLines = '';
        foreach (($props['props'] ?? []) as $propName => $propDef) {
            $default    = $propDef['default'] ?? "''";
            $propsLines .= "    '$propName' => '$default',\n";
        }
        $template = "@props([\n{$propsLines}])\n<div {{ \$attributes }}>\n    {{ \$slot }}\n</div>\n";
        file_put_contents($templatePath, $template);
        $files[] = "$componentDir/$value.blade.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'components', 'name' => $name, 'files' => $files];
    }
}

function scaffoldViewModel(string $name, array $props, string $root, array $scaffold, array &$created): void
{
    $vmDir       = $scaffold['directories']['viewmodels'];
    $vmNamespace = $scaffold['namespaces']['viewmodels'];
    $dataModel   = $scaffold['attributes']['data_model'];
    $viewModel   = $scaffold['attributes']['viewmodel'];
    $classPath   = $root . $vmDir . '/' . $name . '.php';
    $files       = [];

    if (! file_exists($classPath)) {
        $fields = $props['fields'] ?? [];
        $consts = '';
        $properties = '';
        foreach ($fields as $fieldName => $fieldType) {
            $consts     .= "    public const string $fieldName = '$fieldName';\n";
            $properties .= "    public $fieldType \$$fieldName;\n";
        }

        $dataModelShort  = basename(str_replace('\\', '/', $dataModel));
        $viewModelShort  = basename(str_replace('\\', '/', $viewModel));

        $class = <<<PHP
            <?php

            declare(strict_types=1);

            namespace $vmNamespace;

            use $dataModel;
            use $viewModel;

            #[$viewModelShort]
            readonly class $name
            {
                use $dataModelShort;

            $consts
            $properties}

            PHP;

        // Remove common leading whitespace from heredoc
        $class = preg_replace('/^ {12}/m', '', $class);
        file_put_contents($classPath, $class);
        $files[] = "$vmDir/$name.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'viewmodels', 'name' => $name, 'files' => $files];
    }
}

function scaffoldTest(string $name, array $props, string $root, array $scaffold, array &$created): void
{
    $testDir        = $scaffold['directories']['tests_integration'];
    $testNamespace  = $scaffold['namespaces']['tests_integration'];
    $coversRoute    = $scaffold['attributes']['covers_route'];
    $routeNamespace = $scaffold['namespaces']['routes'];
    $classPath      = $root . $testDir . '/' . $name . '.php';
    $files          = [];

    if (! file_exists($classPath)) {
        $coversRoutes     = $props['covers_routes'] ?? [];
        $coversRouteShort = basename(str_replace('\\', '/', $coversRoute));
        $useLines         = "use PHPUnit\\Framework\\Attributes\\Test;\n";
        $attrLine         = '';
        if ($coversRoutes !== []) {
            $useLines .= "use $coversRoute;\n";
            $useLines .= "use $routeNamespace\\Route;\n";
            $routeArgs = implode(', ', array_map(fn(string $r): string => "Route::$r", $coversRoutes));
            $attrLine  = "#[$coversRouteShort($routeArgs)]\n";
        }

        $class = <<<PHP
            <?php

            declare(strict_types=1);

            namespace $testNamespace;

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
        $files[] = "$testDir/$name.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'tests', 'name' => $name, 'files' => $files];
    }
}

function scaffoldController(string $name, array $props, string $root, array $scaffold, array &$created): void
{
    $ctrlDir         = $scaffold['directories']['controllers'];
    $ctrlNamespace   = $scaffold['namespaces']['controllers'];
    $routeNamespace  = $scaffold['namespaces']['routes'];
    $tablesNamespace = $scaffold['namespaces']['tables'];
    $bladeNamespace  = $scaffold['namespaces']['blade'];
    $handlesRoute    = $scaffold['attributes']['handles_route'];
    $persistsAttr    = $scaffold['attributes']['persists'];
    $redirectsToAttr = $scaffold['attributes']['redirects_to'];
    $rendersViewAttr = $scaffold['attributes']['renders_view'];
    $classPath       = $root . $ctrlDir . '/' . $name . '.php';
    $files           = [];

    if (! file_exists($classPath)) {
        $renders     = $props['renders'] ?? null;
        $persists    = $props['persists'] ?? [];
        $redirectsTo = $props['redirects_to'] ?? [];

        $routeName = $props['route'] ?? null;

        $handlesRouteShort  = basename(str_replace('\\', '/', $handlesRoute));
        $persistsShort      = basename(str_replace('\\', '/', $persistsAttr));
        $redirectsToShort   = basename(str_replace('\\', '/', $redirectsToAttr));
        $rendersViewShort   = basename(str_replace('\\', '/', $rendersViewAttr));

        $useLines = '';
        $attrs    = '';

        if ($routeName !== null) {
            $useLines .= "use $handlesRoute;\n";
            $useLines .= "use $routeNamespace\\Route;\n";
            $attrs    .= "#[$handlesRouteShort(Route::$routeName)]\n";
        }

        if ($renders !== null) {
            $useLines .= "use $rendersViewAttr;\n";
            if (!str_contains($useLines, "use $bladeNamespace\\View;")) {
                $useLines .= "use $bladeNamespace\\View;\n";
            }
            $attrs .= "#[$rendersViewShort(View::$renders)]\n";
        }

        foreach ($persists as $model) {
            $useLines .= "use $persistsAttr;\n";
            $useLines .= "use $tablesNamespace\\$model;\n";
            $attrs    .= "#[$persistsShort($model::class)]\n";
        }
        foreach ($redirectsTo as $route) {
            if (!str_contains($useLines, "use $redirectsToAttr;")) {
                $useLines .= "use $redirectsToAttr;\n";
            }
            if (!str_contains($useLines, "use $routeNamespace\\Route;")) {
                $useLines .= "use $routeNamespace\\Route;\n";
            }
            $attrs    .= "#[$redirectsToShort(Route::$route)]\n";
        }

        $class = <<<PHP
            <?php

            declare(strict_types=1);

            namespace $ctrlNamespace;

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
        $files[] = "$ctrlDir/$name.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'controllers', 'name' => $name, 'files' => $files];
    }
}

function scaffoldTable(string $name, array $props, string $root, array $scaffold, array &$created): void
{
    $tablesDir       = $scaffold['directories']['tables'];
    $tablesNamespace = $scaffold['namespaces']['tables'];
    $schemaNamespace = $scaffold['namespaces']['schema'];
    $closedSet       = $scaffold['attributes']['closed_set'];
    $schemaSync      = $scaffold['attributes']['schema_sync'];
    $tableAttr       = $scaffold['attributes']['table'];
    $hasTableName    = $scaffold['attributes']['has_table_name'];
    $dataModel       = $scaffold['attributes']['data_model'];
    $columnAttr      = $scaffold['attributes']['column'];
    $primaryKeyAttr  = $scaffold['attributes']['primary_key'];
    $files           = [];

    $tableName   = $props['table'] ?? strtolower($name) . 's';
    $engine      = $props['engine'] ?? 'InnoDB';
    $columns     = $props['columns'] ?? [];
    $primaryKeys = $props['primary_key'] ?? [];

    $closedSetShort    = basename(str_replace('\\', '/', $closedSet));
    $schemaSyncShort   = basename(str_replace('\\', '/', $schemaSync));
    $tableAttrShort    = basename(str_replace('\\', '/', $tableAttr));
    $hasTableNameShort = basename(str_replace('\\', '/', $hasTableName));
    $dataModelShort    = basename(str_replace('\\', '/', $dataModel));
    $columnShort       = basename(str_replace('\\', '/', $columnAttr));
    $primaryKeyShort   = basename(str_replace('\\', '/', $primaryKeyAttr));

    // --- Columns trait ---
    $traitName = $name . 'Columns';
    $traitPath = $root . $tablesDir . '/' . $traitName . '.php';

    if (! file_exists($traitPath)) {
        $traitBody = '';
        foreach ($columns as $colName => $colDef) {
            $type     = $colDef['type'] ?? 'VARCHAR';
            $length   = $colDef['length'] ?? null;
            $nullable = ($colDef['nullable'] ?? false) ? 'true' : 'false';
            $default  = $colDef['default'] ?? null;
            $comment  = $colDef['comment'] ?? ucfirst(str_replace('_', ' ', $colName));

            $defaultStr = match (true) {
                $default === 'CURRENT_TIMESTAMP' => "$columnShort::CURRENT_TIMESTAMP",
                $default === null                => 'null',
                is_string($default)              => "'" . addslashes($default) . "'",
                default                          => (string) $default,
            };

            $lengthStr = $length !== null ? (string) $length : 'null';

            $pkLine = in_array($colName, $primaryKeys, true)
                ? "    #[$primaryKeyShort(columns: [])]\n"
                : '';

            $phpType     = $nullable === 'true' ? '?string' : 'string';
            $traitBody .= <<<COLUMN
                /** @see \$$colName */
                public const string $colName = '$colName';
                #[$columnShort(
                    DataType: DataType::$type,
                    length: $lengthStr,
                    precision: null,
                    scale: null,
                    unsigned: false,
                    nullable: $nullable,
                    auto_increment: false,
                    default: $defaultStr,
                    values: null,
                    comment: '$comment',
                )]
            {$pkLine}    public readonly $phpType \$$colName;


            COLUMN;
        }

        $trait = <<<PHP
            <?php

            declare(strict_types=1);

            namespace $tablesNamespace;

            use $columnAttr;
            use $primaryKeyAttr;
            use $schemaNamespace\\DataType;

            trait $traitName
            {
            $traitBody}

            PHP;

        $trait = preg_replace('/^ {12}/m', '', $trait);
        file_put_contents($traitPath, $trait);
        $files[] = "$tablesDir/$traitName.php";
    }

    // --- Main table class ---
    $classPath = $root . $tablesDir . '/' . $name . '.php';

    if (! file_exists($classPath)) {
        $class = <<<PHP
            <?php

            declare(strict_types=1);

            namespace $tablesNamespace;

            use $closedSet;
            use $dataModel;
            use $hasTableName;
            use $schemaSync;
            use $tableAttr;
            use $schemaNamespace\\Charset;
            use $schemaNamespace\\Collation;
            use $schemaNamespace\\Engine;
            use $schemaNamespace\\SchemaSource;
            use ZeroToProd\\Thryds\\UI\\Domain;

            #[$closedSetShort(
                Domain::database_table_columns,
                addCase: <<<TEXT
                1. Add enum case with #[$columnShort] attribute.
                2. Write a migration to ALTER TABLE $tableName ADD COLUMN ...
                TEXT
            )]
            #[$schemaSyncShort(SchemaSource::attributes)]
            #[$tableAttrShort(
                TableName: TableName::$tableName,
                Engine: Engine::$engine,
                Charset: Charset::utf8mb4,
                Collation: Collation::utf8mb4_unicode_ci
            )]
            readonly class $name
            {
                use $dataModelShort;
                use $hasTableNameShort;
                use $traitName;
            }

            PHP;

        $class = preg_replace('/^ {12}/m', '', $class);
        file_put_contents($classPath, $class);
        $files[] = "$tablesDir/$name.php";
    }

    // --- Add case to TableName enum if missing ---
    $tableNamePath = $root . $tablesDir . '/TableName.php';
    if (file_exists($tableNamePath)) {
        $enumContent = file_get_contents($tableNamePath);
        if (! str_contains($enumContent, "case $tableName")) {
            $enumContent = preg_replace(
                '/^}\s*$/m',
                "    case $tableName = '$tableName';\n}\n",
                $enumContent,
            );
            file_put_contents($tableNamePath, $enumContent);
            $files[] = "$tablesDir/TableName.php (added case $tableName)";
        }
    }

    if ($files !== []) {
        $created[] = ['section' => 'tables', 'name' => $name, 'files' => $files];
    }
}

function scaffoldEnum(string $name, array $props, string $root, array $scaffold, array &$created): void
{
    $enumDir       = $scaffold['directories']['enums'];
    $enumNamespace = $scaffold['namespaces']['enums'];
    $closedSet     = $scaffold['attributes']['closed_set'];
    $classPath     = $root . $enumDir . '/' . $name . '.php';
    $files         = [];

    if (! file_exists($classPath)) {
        $cases        = $props['cases'] ?? [];
        $closedSetShort = basename(str_replace('\\', '/', $closedSet));

        // Derive domain name: PascalCase → snake_case, then pluralize.
        $domain = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)) . 's';

        $caseLines = '';
        foreach ($cases as $case) {
            $caseLines .= "    case $case = '$case';\n";
        }

        $class = <<<PHP
            <?php

            declare(strict_types=1);

            namespace $enumNamespace;

            use $closedSet;

            #[$closedSetShort(
                Domain::$domain,
                addCase: '1. Add enum case.'
            )]
            enum $name: string
            {
            $caseLines}

            PHP;

        $class = preg_replace('/^ {12}/m', '', $class);
        file_put_contents($classPath, $class);
        $files[] = "$enumDir/$name.php";
    }

    if ($files !== []) {
        $created[] = ['section' => 'enums', 'name' => $name, 'files' => $files];
    }
}
