<?php

declare(strict_types=1);

/**
 * Generate a complete graph of all PHP attributes in the project.
 *
 * Outputs YAML (structured data) or Mermaid (class diagram) based on --format= argument.
 *
 * Usage: docker compose exec web php scripts/attribute-graph.php [--format=yaml|mermaid] [--output=FILE] [--dir=src]
 * Via Composer: ./run list:attributes [-- --format=mermaid --output=graph.mmd]
 *
 * Exit 0 on success.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$format = 'yaml';
$output = null;
$dirs = ['src'];

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, strlen('--format='));
    } elseif (str_starts_with($arg, '--output=')) {
        $output = substr($arg, strlen('--output='));
    } elseif (str_starts_with($arg, '--dir=')) {
        $dirs = explode(',', substr($arg, strlen('--dir=')));
    }
}

if (! in_array($format, ['yaml', 'mermaid'], true)) {
    fwrite(STDERR, "Unknown format: $format. Use yaml or mermaid.\n");
    exit(1);
}

$projectRoot = realpath(__DIR__ . '/../') . '/';

// --- Step 1: Discover classes ---

/** Extract FQCN from a PHP file by reading namespace and class/enum/trait declaration. */
function extractFqcn(string $filePath): ?string
{
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        return null;
    }

    $namespace = null;
    if (preg_match('/^\s*namespace\s+([A-Za-z0-9_\\\\]+)\s*;/m', $contents, $m)) {
        $namespace = $m[1];
    }

    if (preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|enum|trait|interface)\s+([A-Za-z0-9_]+)/m', $contents, $m)) {
        return $namespace !== null ? $namespace . '\\' . $m[1] : $m[1];
    }

    return null;
}

/** Recursively find all .php files in a directory. */
function findPhpFiles(string $dir): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    sort($files);
    return $files;
}

$classes = [];
foreach ($dirs as $dir) {
    $absDir = $projectRoot . ltrim($dir, '/');
    if (! is_dir($absDir)) {
        fwrite(STDERR, "Directory not found: $dir\n");
        continue;
    }
    foreach (findPhpFiles($absDir) as $file) {
        $fqcn = extractFqcn($file);
        if ($fqcn === null) {
            continue;
        }
        if (! class_exists($fqcn, true) && ! enum_exists($fqcn, true) && ! interface_exists($fqcn, true) && ! trait_exists($fqcn, true)) {
            continue;
        }
        $classes[$fqcn] = str_replace($projectRoot, '', $file);
    }
}

// --- Step 2: Reflection walk ---

/** Serialize an attribute argument value to a YAML-safe scalar/array. */
function serializeValue(mixed $value): mixed
{
    if ($value instanceof BackedEnum) {
        return $value->value;
    }
    if ($value instanceof UnitEnum) {
        return $value->name;
    }
    if (is_array($value)) {
        return array_map(serializeValue(...), $value);
    }
    if (is_object($value)) {
        return $value::class;
    }
    return $value;
}

/** Collect attributes from a list of ReflectionAttribute instances. */
function collectAttributes(array $reflectionAttributes): array
{
    $result = [];
    foreach ($reflectionAttributes as $attr) {
        // Skip PHP's built-in #[Attribute] meta-attribute.
        if ($attr->getName() === Attribute::class) {
            continue;
        }
        $shortName = substr(strrchr($attr->getName(), '\\') ?: ('\\' . $attr->getName()), 1);
        $args = array_map(serializeValue(...), $attr->getArguments());

        if ($attr->isRepeated()) {
            $result[$shortName][] = $args === [] ? true : (count($args) === 1 && array_key_exists(0, $args) ? $args[0] : $args);
        } else {
            $result[$shortName] = $args === [] ? true : (count($args) === 1 && array_key_exists(0, $args) ? $args[0] : $args);
        }
    }
    return $result;
}

/** Determine the kind of a reflected class. */
function classKind(ReflectionClass $ref): string
{
    if ($ref->isEnum()) {
        return 'enum';
    }
    if ($ref->isInterface()) {
        return 'interface';
    }
    if ($ref->isTrait()) {
        return 'trait';
    }
    return 'class';
}

$graph = [];

foreach ($classes as $fqcn => $relPath) {
    $ref = new ReflectionClass($fqcn);
    $entry = [
        'file' => $relPath,
        'kind' => classKind($ref),
    ];

    // Class-level attributes.
    $classAttrs = collectAttributes($ref->getAttributes());
    if ($classAttrs !== []) {
        $entry['attributes'] = $classAttrs;
    }

    // Properties (declared in this class only).
    $properties = [];
    foreach ($ref->getProperties() as $prop) {
        if ($prop->getDeclaringClass()->getName() !== $fqcn) {
            continue;
        }
        $propAttrs = collectAttributes($prop->getAttributes());
        if ($propAttrs !== []) {
            $properties[$prop->getName()] = ['attributes' => $propAttrs];
        }
    }
    if ($properties !== []) {
        $entry['properties'] = $properties;
    }

    // Methods (declared in this class only).
    $methods = [];
    foreach ($ref->getMethods() as $method) {
        if ($method->getDeclaringClass()->getName() !== $fqcn) {
            continue;
        }
        $methodAttrs = collectAttributes($method->getAttributes());
        if ($methodAttrs !== []) {
            $methods[$method->getName()] = ['attributes' => $methodAttrs];
        }
    }
    if ($methods !== []) {
        $entry['methods'] = $methods;
    }

    // Constants and enum cases.
    $constants = [];
    foreach ($ref->getReflectionConstants() as $const) {
        if ($const->getDeclaringClass()->getName() !== $fqcn) {
            continue;
        }
        $constAttrs = collectAttributes($const->getAttributes());
        if ($constAttrs !== []) {
            $constants[$const->getName()] = ['attributes' => $constAttrs];
        }
    }
    if ($constants !== []) {
        $key = $ref->isEnum() ? 'cases' : 'constants';
        $entry[$key] = $constants;
    }

    // Skip classes with no attributes anywhere.
    if (! isset($entry['attributes']) && ! isset($entry['properties']) && ! isset($entry['methods']) && ! isset($entry['cases']) && ! isset($entry['constants'])) {
        continue;
    }

    $graph[$fqcn] = $entry;
}

ksort($graph);

// --- Step 3: Derive edges ---

$shortNames = [];
foreach ($graph as $fqcn => $entry) {
    $shortNames[$fqcn] = substr(strrchr($fqcn, '\\') ?: ('\\' . $fqcn), 1);
}
$fqcnByShort = array_flip($shortNames);

$edges = [];
foreach ($graph as $fqcn => $entry) {
    deriveEdges($shortNames[$fqcn], $entry, $shortNames, $fqcnByShort, $edges);
}

// Deduplicate edges.
$uniqueEdges = [];
$seen = [];
foreach ($edges as $edge) {
    $key = $edge['from'] . '|' . $edge['to'] . '|' . $edge['rel'] . '|' . ($edge['source'] ?? '');
    if (! isset($seen[$key])) {
        $seen[$key] = true;
        $uniqueEdges[] = $edge;
    }
}
$edges = $uniqueEdges;

// --- Step 4: Output ---

if ($format === 'yaml') {
    // Emit edges as a top-level section alongside the nodes.
    $yamlEdges = array_map(static function (array $e): array {
        $entry = ['from' => $e['from'], 'to' => $e['to'], 'rel' => $e['rel']];
        if ($e['source'] !== 'class') {
            $entry['source'] = $e['source'];
        }
        return $entry;
    }, $edges);
    $output_data = ['edges' => $yamlEdges, 'nodes' => $graph];
    $result = Yaml::dump($output_data, 6, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
} else {
    // Mermaid class diagram.
    $lines = ['classDiagram'];

    foreach ($graph as $fqcn => $entry) {
        $short = $shortNames[$fqcn];
        $stereotype = $entry['kind'];
        $lines[] = "    class $short {";
        $lines[] = "        <<$stereotype>>";

        foreach ($entry['attributes'] ?? [] as $attrName => $attrArgs) {
            $lines[] = '        +' . formatMermaidAttr($attrName, $attrArgs);
        }

        $lines[] = '    }';
    }

    $edgeSeen = [];
    foreach ($edges as $edge) {
        $key = $edge['from'] . '|' . $edge['to'] . '|' . $edge['rel'];
        if (! isset($edgeSeen[$key])) {
            $edgeSeen[$key] = true;
            $lines[] = "    {$edge['from']} ..> {$edge['to']} : {$edge['rel']}";
        }
    }

    $result = implode("\n", $lines) . "\n";
}

/** Strip characters that Mermaid interprets as syntax inside class members. */
function sanitizeMermaid(string $text): string
{
    return str_replace(
        ["\n", "\r", '{', '}', '[', ']', '<', '>', '#', ';', '`', '\\', '$', '~', '"'],
        [' ',  ' ',  '(', ')', '(', ')', '',  '',  '',  ',', "'", '.',  '',  '-', "'"],
        $text,
    );
}

/** Format an attribute for display in a Mermaid class member line. */
function formatMermaidAttr(string $name, mixed $args): string
{
    if ($args === true) {
        return $name;
    }
    if (is_scalar($args)) {
        $s = sanitizeMermaid((string) $args);
        if (strlen($s) > 50) {
            $s = substr($s, 0, 47) . '...';
        }
        return $name . ' : ' . $s;
    }
    if (is_array($args)) {
        $summary = sanitizeMermaid(summarizeArgs($args));
        if (strlen($summary) > 50) {
            $summary = substr($summary, 0, 47) . '...';
        }
        return $name . ' : ' . $summary;
    }
    return $name;
}

/** Recursively summarize mixed args into a short string. */
function summarizeArgs(mixed $value): string
{
    if (is_array($value)) {
        $parts = [];
        foreach ($value as $k => $v) {
            $s = summarizeArgs($v);
            $parts[] = is_string($k) ? "$k: $s" : $s;
        }
        return implode(', ', $parts);
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if ($value === null) {
        return 'null';
    }
    return (string) $value;
}

/**
 * Walk attribute arguments to find references to other classes in the graph, producing edges.
 *
 * Each edge: ['from' => short, 'to' => short, 'rel' => attr_name, 'source' => location].
 * Source is 'class', 'case:name', 'property:name', 'method:name', or 'constant:name'.
 */
function deriveEdges(string $fromShort, array $entry, array $shortNames, array $fqcnByShort, array &$edges): void
{
    $allAttrs = [];

    // Gather all attributes with their source location.
    foreach ($entry['attributes'] ?? [] as $attrName => $args) {
        $allAttrs[] = [$attrName, $args, 'class'];
    }
    foreach (['properties' => 'property', 'methods' => 'method', 'cases' => 'case', 'constants' => 'constant'] as $section => $prefix) {
        foreach ($entry[$section] ?? [] as $targetName => $targets) {
            foreach ($targets['attributes'] ?? [] as $attrName => $args) {
                $allAttrs[] = [$attrName, $args, "$prefix:$targetName"];
            }
        }
    }

    foreach ($allAttrs as [$attrName, $args, $source]) {
        $values = is_array($args) ? flattenValues($args) : [$args];
        foreach ($values as $val) {
            if (! is_string($val)) {
                continue;
            }
            $targetShort = resolveTarget($val, $shortNames, $fqcnByShort);
            if ($targetShort !== null && $targetShort !== $fromShort) {
                $edges[] = [
                    'from' => $fromShort,
                    'to' => $targetShort,
                    'rel' => strtolower($attrName),
                    'source' => $source,
                ];
            }
        }
    }
}

/** Resolve a string value to a short class name in the graph, or null. */
function resolveTarget(string $val, array $shortNames, array $fqcnByShort): ?string
{
    if (isset($fqcnByShort[$val])) {
        return $val;
    }
    foreach ($shortNames as $fqcn => $short) {
        if ($val === $fqcn) {
            return $short;
        }
    }
    return null;
}

/** Recursively flatten nested arrays into a single list of scalar values. */
function flattenValues(mixed $value): array
{
    if (is_array($value)) {
        $flat = [];
        foreach ($value as $v) {
            $flat = [...$flat, ...flattenValues($v)];
        }
        return $flat;
    }
    return [$value];
}

if ($output !== null) {
    $absOutput = str_starts_with($output, '/') ? $output : $projectRoot . $output;
    $dir = dirname($absOutput);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($absOutput, $result);
    fwrite(STDERR, "Written to $output\n");
} else {
    echo $result;
}
