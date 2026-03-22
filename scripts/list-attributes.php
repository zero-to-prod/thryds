<?php

declare(strict_types=1);

/**
 * Generate a complete graph of all PHP attributes in the project.
 *
 * Outputs YAML, JSON (structured data), Markdown (readable document), or Mermaid (class diagram) based on --format= argument.
 *
 * Usage: docker compose exec web php scripts/list-attributes.php [--config=FILE] [--format=yaml|json|markdown|mermaid] [--output=FILE] [--dir=src] [filters...]
 * Via Composer: ./run list:attributes [-- --format=markdown --node=RegisterController --layer=controllers]
 *
 * Filters (all repeatable, combined with AND across types, OR within same type):
 *   --node=<ShortName>   Include node and its one-hop neighbors via edges
 *   --layer=<layer>      Filter by semantic layer (core, views, controllers, etc.)
 *   --kind=<kind>        Filter by kind (class, enum, interface, trait)
 *   --attr=<Attribute>   Filter to nodes carrying a specific attribute name
 *   --rel=<rel>          Filter edges to specific relationship types
 *   --file=<substring>   Filter to nodes whose file path contains substring
 *
 * Exit 0 on success.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$format = 'yaml';
$output = null;
$configPath = null;
$dirs = ['src'];
$filterNodes = [];
$filterLayers = [];
$filterKinds = [];
$filterAttrs = [];
$filterRels = [];
$filterFiles = [];

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, strlen('--format='));
    } elseif (str_starts_with($arg, '--output=')) {
        $output = substr($arg, strlen('--output='));
    } elseif (str_starts_with($arg, '--config=')) {
        $configPath = substr($arg, strlen('--config='));
    } elseif (str_starts_with($arg, '--dir=')) {
        $dirs = explode(',', substr($arg, strlen('--dir=')));
    } elseif (str_starts_with($arg, '--node=')) {
        $filterNodes[] = substr($arg, strlen('--node='));
    } elseif (str_starts_with($arg, '--layer=')) {
        $filterLayers[] = substr($arg, strlen('--layer='));
    } elseif (str_starts_with($arg, '--kind=')) {
        $filterKinds[] = substr($arg, strlen('--kind='));
    } elseif (str_starts_with($arg, '--attr=')) {
        $filterAttrs[] = substr($arg, strlen('--attr='));
    } elseif (str_starts_with($arg, '--rel=')) {
        $filterRels[] = substr($arg, strlen('--rel='));
    } elseif (str_starts_with($arg, '--file=')) {
        $filterFiles[] = substr($arg, strlen('--file='));
    }
}

$hasFilters = $filterNodes !== [] || $filterLayers !== [] || $filterKinds !== [] || $filterAttrs !== [] || $filterRels !== [] || $filterFiles !== [];

if (! in_array($format, ['yaml', 'json', 'markdown', 'mermaid'], true)) {
    fwrite(STDERR, "Unknown format: $format. Use yaml, json or mermaid.\n");
    exit(1);
}

$projectRoot = realpath(__DIR__ . '/../') . '/';

// --- Load configuration ---
$configFile = $configPath !== null
    ? (str_starts_with($configPath, '/') ? $configPath : $projectRoot . $configPath)
    : $projectRoot . 'attribute-graph.yaml';
$config = is_file($configFile) ? Yaml::parseFile($configFile) : [];

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

/** Remove null and false values from a named argument map (compact representation). */
function compactArgs(array $args): array
{
    // Only compact named-key maps, not positional arrays.
    if ($args === [] || array_is_list($args)) {
        return $args;
    }
    return array_filter($args, static fn(mixed $v): bool => $v !== null && $v !== false);
}

/** Resolve positional (integer-keyed) arguments to named parameter keys via constructor reflection. */
function namePositionalArgs(string $attributeClass, array $args): array
{
    // Nothing to resolve if already all named or empty.
    $hasPositional = false;
    foreach ($args as $key => $_) {
        if (is_int($key)) {
            $hasPositional = true;
            break;
        }
    }
    if (! $hasPositional) {
        return $args;
    }

    try {
        $params = new ReflectionClass($attributeClass)->getConstructor()?->getParameters() ?? [];
    } catch (ReflectionException) {
        return $args;
    }

    $named = [];
    foreach ($args as $key => $value) {
        if (is_int($key) && isset($params[$key])) {
            $named[$params[$key]->getName()] = $value;
        } else {
            $named[$key] = $value;
        }
    }
    return $named;
}

/** Check if an attribute class is declared IS_REPEATABLE. */
function isRepeatableAttribute(string $attributeClass): bool
{
    static $cache = [];
    if (isset($cache[$attributeClass])) {
        return $cache[$attributeClass];
    }
    if (! class_exists($attributeClass)) {
        return $cache[$attributeClass] = false;
    }
    $ref = new ReflectionClass($attributeClass);
    $attrs = $ref->getAttributes(Attribute::class);
    if ($attrs === []) {
        return $cache[$attributeClass] = false;
    }
    return $cache[$attributeClass] = ($attrs[0]->newInstance()->flags & Attribute::IS_REPEATABLE) !== 0;
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
        $args = compactArgs(namePositionalArgs($attr->getName(), array_map(serializeValue(...), $attr->getArguments())));

        $value = $args === [] ? true : $args;

        if (isRepeatableAttribute($attr->getName())) {
            $result[$shortName][] = $value;
        } else {
            $result[$shortName] = $value;
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
// Raw edges collected during reflection, before serialization.
// Each: ['from_fqcn' => string, 'attr' => string, 'target_fqcn' => string, 'source' => string]
$rawEdges = [];

/**
 * Extract class references from raw attribute arguments (before serialization).
 * Returns FQCNs found as enum class owners or class-string values.
 */
function extractClassRefs(array $args): array
{
    $refs = [];
    foreach ($args as $val) {
        if ($val instanceof BackedEnum || $val instanceof UnitEnum) {
            $refs[] = $val::class;
        } elseif (is_string($val) && str_contains($val, '\\') && (class_exists($val) || enum_exists($val) || interface_exists($val))) {
            $refs[] = $val;
        } elseif (is_array($val)) {
            $refs = [...$refs, ...extractClassRefs($val)];
        }
    }
    return $refs;
}

/** Check if an attribute constructor has a typed (non-string) parameter matching the target. */
function isTypedParameterReference(string $attributeClass, string $targetFqcn): bool
{
    try {
        $params = new ReflectionClass($attributeClass)->getConstructor()?->getParameters() ?? [];
    } catch (ReflectionException) {
        return false;
    }
    foreach ($params as $param) {
        $type = $param->getType();
        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            if ($targetFqcn === $type->getName() || is_a($targetFqcn, $type->getName(), true)) {
                return true;
            }
        }
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof ReflectionNamedType && ! $subType->isBuiltin()) {
                    if ($targetFqcn === $subType->getName() || is_a($targetFqcn, $subType->getName(), true)) {
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

/** Resolve EdgeKind from a target's ClosedSet Domain case via its #[Group(EdgeKind)] attribute. */
function resolveEdgeKindFromTarget(string $targetFqcn, array $config): ?string
{
    static $cache = [];
    if (array_key_exists($targetFqcn, $cache)) {
        return $cache[$targetFqcn];
    }

    $closedSetClass = $config['closedset']['class'] ?? null;
    $domainProperty = $config['closedset']['domain_property'] ?? null;
    $groupClass = $config['group']['class'] ?? null;
    $groupValueProperty = $config['group']['value_property'] ?? null;
    $edgeKindClass = $config['edge_kind']['class'] ?? null;

    if ($closedSetClass === null || $groupClass === null || $edgeKindClass === null) {
        return $cache[$targetFqcn] = null;
    }

    if (! class_exists($targetFqcn) && ! enum_exists($targetFqcn)) {
        return $cache[$targetFqcn] = null;
    }
    $ref = new ReflectionClass($targetFqcn);
    $closedSetAttrs = $ref->getAttributes($closedSetClass);
    if ($closedSetAttrs === []) {
        return $cache[$targetFqcn] = null;
    }
    $domainCase = $closedSetAttrs[0]->newInstance()->{$domainProperty};
    $caseRef = new ReflectionEnumBackedCase($domainCase::class, $domainCase->name);
    $groupAttrs = $caseRef->getAttributes($groupClass);
    if ($groupAttrs === []) {
        return $cache[$targetFqcn] = null;
    }
    $group = $groupAttrs[0]->newInstance();
    $groupValue = $group->{$groupValueProperty};
    if ($groupValue instanceof $edgeKindClass) {
        return $cache[$targetFqcn] = $groupValue->value;
    }
    return $cache[$targetFqcn] = null;
}

/**
 * Infer the semantic edge kind from the attribute's constructor signature and the target's Domain.
 *
 * Typed parameters → look up target's ClosedSet Domain → Domain case's Group(EdgeKind).
 * String (class-string) parameters → data-flow (runtime reference).
 */
function resolveEdgeKind(string $attributeClass, string $targetFqcn, array $config): ?string
{
    if (isTypedParameterReference($attributeClass, $targetFqcn)) {
        return resolveEdgeKindFromTarget($targetFqcn, $config);
    }
    return $config['edge_kind']['default'] ?? null;
}

/** Collect raw edges from ReflectionAttribute instances for a given source. */
function collectEdges(array $reflectionAttributes, string $fromFqcn, string $source, array &$rawEdges, array $config): void
{
    foreach ($reflectionAttributes as $attr) {
        if ($attr->getName() === Attribute::class) {
            continue;
        }
        $shortName = substr(strrchr($attr->getName(), '\\') ?: ('\\' . $attr->getName()), 1);
        $args = compactArgs(namePositionalArgs($attr->getName(), array_map(serializeValue(...), $attr->getArguments())));
        $refs = extractClassRefs($attr->getArguments());
        foreach ($refs as $targetFqcn) {
            if ($targetFqcn !== $fromFqcn) {
                $rawEdges[] = [
                    'from_fqcn' => $fromFqcn,
                    'attr' => $shortName,
                    'target_fqcn' => $targetFqcn,
                    'source' => $source,
                    'args' => $args,
                    'kind' => resolveEdgeKind($attr->getName(), $targetFqcn, $config),
                ];
            }
        }
    }
}

/** Read the PSR-4 root namespace for a source directory from composer.json. */
function resolveRootNamespace(string $projectRoot, string $dir): ?string
{
    $composerPath = $projectRoot . 'composer.json';
    if (! is_file($composerPath)) {
        return null;
    }
    $composer = json_decode(file_get_contents($composerPath), true);
    $psr4 = array_merge($composer['autoload']['psr-4'] ?? [], $composer['autoload-dev']['psr-4'] ?? []);
    foreach ($psr4 as $namespace => $path) {
        if (rtrim($path, '/') === rtrim($dir, '/')) {
            return $namespace;
        }
    }
    return null;
}

/** Build the namespace-segment-to-layer map from the Layer enum via reflection. */
function buildLayerMap(array $config): array
{
    $layerClass = $config['layer']['class'] ?? null;
    $segmentAttribute = $config['layer']['segment_attribute'] ?? null;
    $segmentProperty = $config['layer']['segment_property'] ?? 'namespace';

    if ($layerClass === null || ! enum_exists($layerClass)) {
        return [];
    }

    $map = [];
    foreach ($layerClass::cases() as $case) {
        $caseRef = new ReflectionEnumBackedCase($layerClass, $case->name);
        if ($segmentAttribute !== null) {
            $segmentAttrs = $caseRef->getAttributes($segmentAttribute);
            $segment = $segmentAttrs !== [] ? $segmentAttrs[0]->newInstance()->{$segmentProperty} : ucfirst($case->name);
        } else {
            $segment = ucfirst($case->name);
        }
        $map[$segment] = $case->value;
    }
    return $map;
}

/** Derive a semantic layer from a FQCN based on the namespace segment after the project root. */
function deriveLayer(string $fqcn, string $rootNamespace, array $layerMap): string
{
    $relative = preg_replace('/^' . preg_quote($rootNamespace, '/') . '/', '', $fqcn);
    if ($relative === $fqcn) {
        $parts = explode('\\', $fqcn);
        return strtolower($parts[0]);
    }

    $firstSegment = explode('\\', $relative)[0];

    return $layerMap[$firstSegment] ?? (str_contains($relative, '\\') ? strtolower($firstSegment) : 'core');
}

$rootNamespace = resolveRootNamespace($projectRoot, $dirs[0]) ?? '';
$layerMap = buildLayerMap($config);

foreach ($classes as $fqcn => $relPath) {
    $ref = new ReflectionClass($fqcn);
    $entry = [
        'file' => $relPath,
        'kind' => classKind($ref),
        'layer' => deriveLayer($fqcn, $rootNamespace, $layerMap),
    ];

    // Class-level attributes.
    $refAttrs = $ref->getAttributes();
    $classAttrs = collectAttributes($refAttrs);
    collectEdges($refAttrs, $fqcn, 'class', $rawEdges, $config);
    if ($classAttrs !== []) {
        $entry['attributes'] = $classAttrs;
    }

    // Properties (declared in this class only).
    $properties = [];
    foreach ($ref->getProperties() as $prop) {
        if ($prop->getDeclaringClass()->getName() !== $fqcn) {
            continue;
        }
        $propRefAttrs = $prop->getAttributes();
        $propAttrs = collectAttributes($propRefAttrs);
        collectEdges($propRefAttrs, $fqcn, 'property:' . $prop->getName(), $rawEdges, $config);
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
        $methodRefAttrs = $method->getAttributes();
        $methodAttrs = collectAttributes($methodRefAttrs);
        collectEdges($methodRefAttrs, $fqcn, 'method:' . $method->getName(), $rawEdges, $config);
        if ($methodAttrs !== []) {
            $methods[$method->getName()] = ['attributes' => $methodAttrs];
        }
    }
    if ($methods !== []) {
        $entry['methods'] = $methods;
    }

    // Constants and enum cases.
    $constants = [];
    $isBackedEnum = $ref->isEnum() && method_exists($fqcn, 'tryFrom');
    foreach ($ref->getReflectionConstants() as $const) {
        if ($const->getDeclaringClass()->getName() !== $fqcn) {
            continue;
        }
        $constRefAttrs = $const->getAttributes();
        $constAttrs = collectAttributes($constRefAttrs);
        $prefix = $ref->isEnum() ? 'case' : 'constant';
        collectEdges($constRefAttrs, $fqcn, "$prefix:" . $const->getName(), $rawEdges, $config);
        if ($constAttrs !== [] || ($isBackedEnum && $const->isEnumCase())) {
            $caseEntry = [];
            if ($isBackedEnum && $const->isEnumCase()) {
                try {
                    $caseEntry['value'] = $const->getValue()->value;
                } catch (\Throwable) {
                    // Backing value depends on an unavailable constant.
                }
            }
            if ($constAttrs !== []) {
                $caseEntry['attributes'] = $constAttrs;
            }
            $constants[$const->getName()] = $caseEntry;
        }
    }
    if ($constants !== []) {
        $key = $ref->isEnum() ? 'cases' : 'constants';
        $entry[$key] = $constants;
    }

    // Skip classes with no attributes anywhere — unless they are PHP attribute definitions.
    $isAttributeDefinition = $ref->getAttributes(Attribute::class) !== [];
    if (! $isAttributeDefinition && ! isset($entry['attributes']) && ! isset($entry['properties']) && ! isset($entry['methods']) && ! isset($entry['cases']) && ! isset($entry['constants'])) {
        continue;
    }

    if ($isAttributeDefinition) {
        $entry['is_attribute'] = true;
    }

    $graph[$fqcn] = $entry;
}

ksort($graph);

// --- Step 2b: Apply filters ---

/** Collect all attribute names present anywhere on a node entry. */
function collectAllAttrNames(array $entry): array
{
    $names = array_keys($entry['attributes'] ?? []);
    foreach ($entry['properties'] ?? [] as $propData) {
        $names = [...$names, ...array_keys($propData['attributes'] ?? [])];
    }
    foreach ($entry['methods'] ?? [] as $methodData) {
        $names = [...$names, ...array_keys($methodData['attributes'] ?? [])];
    }
    foreach (['cases', 'constants'] as $section) {
        foreach ($entry[$section] ?? [] as $caseData) {
            $names = [...$names, ...array_keys($caseData['attributes'] ?? [])];
        }
    }
    return array_unique($names);
}

if ($hasFilters) {
    // Build short-name-to-FQCN map for --node lookups.
    $shortToFqcn = [];
    foreach ($graph as $fqcn => $entry) {
        $short = substr(strrchr($fqcn, '\\') ?: ('\\' . $fqcn), 1);
        $shortToFqcn[$short] = $fqcn;
    }

    // Phase 1: Apply layer, kind, attr, file filters (AND across types, OR within).
    $candidates = $graph;
    if ($filterLayers !== []) {
        $candidates = array_filter($candidates, static fn(array $e): bool => in_array($e['layer'], $filterLayers, true));
    }
    if ($filterKinds !== []) {
        $candidates = array_filter($candidates, static fn(array $e): bool => in_array($e['kind'], $filterKinds, true));
    }
    if ($filterAttrs !== []) {
        $candidates = array_filter($candidates, static function (array $e) use ($filterAttrs): bool {
            $nodeAttrs = collectAllAttrNames($e);
            foreach ($filterAttrs as $wanted) {
                if (in_array($wanted, $nodeAttrs, true)) {
                    return true;
                }
            }
            return false;
        });
    }
    if ($filterFiles !== []) {
        $candidates = array_filter($candidates, static function (array $e) use ($filterFiles): bool {
            foreach ($filterFiles as $sub) {
                if (str_contains($e['file'], $sub)) {
                    return true;
                }
            }
            return false;
        });
    }

    // Phase 2: --node filter with one-hop neighbor inclusion.
    if ($filterNodes !== []) {
        // Resolve requested node short names to FQCNs.
        $seedFqcns = [];
        foreach ($filterNodes as $name) {
            if (isset($shortToFqcn[$name])) {
                $seedFqcns[] = $shortToFqcn[$name];
            }
            // Also allow FQCN directly.
            if (isset($graph[$name])) {
                $seedFqcns[] = $name;
            }
        }

        // Find one-hop neighbors via raw edges.
        $neighborFqcns = $seedFqcns;
        foreach ($rawEdges as $raw) {
            if (in_array($raw['from_fqcn'], $seedFqcns, true)) {
                $neighborFqcns[] = $raw['target_fqcn'];
            }
            if (in_array($raw['target_fqcn'], $seedFqcns, true)) {
                $neighborFqcns[] = $raw['from_fqcn'];
            }
        }
        $neighborFqcns = array_unique($neighborFqcns);

        // Intersect: node filter AND other filters.
        $candidates = array_filter($candidates, static fn(array $e, string $fqcn): bool => in_array($fqcn, $neighborFqcns, true), ARRAY_FILTER_USE_BOTH);
    }

    $graph = $candidates;
}

// --- Step 3: Derive edges ---

$shortNames = [];
foreach ($graph as $fqcn => $entry) {
    $shortNames[$fqcn] = substr(strrchr($fqcn, '\\') ?: ('\\' . $fqcn), 1);
}

// Also map FQCNs outside the graph to short names for edge targets.
$allShortNames = $shortNames;
foreach ($rawEdges as $raw) {
    if (! isset($allShortNames[$raw['target_fqcn']])) {
        $allShortNames[$raw['target_fqcn']] = substr(strrchr($raw['target_fqcn'], '\\') ?: ('\\' . $raw['target_fqcn']), 1);
    }
}

// Build deduplicated edge list.
$edges = [];
$seen = [];
foreach ($rawEdges as $raw) {
    $fromShort = $shortNames[$raw['from_fqcn']] ?? null;
    $toShort = $allShortNames[$raw['target_fqcn']] ?? null;
    if ($fromShort === null || $toShort === null || $fromShort === $toShort) {
        continue;
    }
    $edge = [
        'from' => $fromShort,
        'to' => $toShort,
        'rel' => strtolower($raw['attr']),
        'kind' => $raw['kind'],
        'source' => $raw['source'],
        'args' => $raw['args'] ?? [],
        'from_file' => $graph[$raw['from_fqcn']]['file'] ?? null,
        'to_file' => $graph[$raw['target_fqcn']]['file'] ?? null,
    ];
    $key = $edge['from'] . '|' . $edge['to'] . '|' . $edge['rel'] . '|' . $edge['source'];
    if (! isset($seen[$key])) {
        $seen[$key] = true;
        $edges[] = $edge;
    }
}

// Apply --rel filter on edges.
if ($filterRels !== []) {
    $edges = array_values(array_filter($edges, static fn(array $e): bool => in_array($e['rel'], $filterRels, true)));
}

// Build reverse-edge index: target node → list of dependents that reference it.
$dependents = [];
foreach ($edges as $edge) {
    $entry = ['from' => $edge['from'], 'rel' => $edge['rel']];
    if ($edge['source'] !== 'class') {
        $entry['source'] = $edge['source'];
    }
    $dependents[$edge['to']][] = $entry;
}
ksort($dependents);

// --- Step 4: Output ---

if ($format === 'yaml') {
    // Emit edges as a top-level section alongside the nodes.
    $yamlEdges = array_map(static function (array $e): array {
        $entry = ['from' => $e['from'], 'to' => $e['to'], 'rel' => $e['rel']];
        if ($e['kind'] !== null) {
            $entry['kind'] = $e['kind'];
        }
        if ($e['source'] !== 'class') {
            $entry['source'] = $e['source'];
        }
        if ($e['args'] !== []) {
            $entry['args'] = $e['args'];
        }
        if ($e['from_file'] !== null) {
            $entry['from_file'] = $e['from_file'];
        }
        if ($e['to_file'] !== null) {
            $entry['to_file'] = $e['to_file'];
        }
        return $entry;
    }, $edges);
    // Build layer index: layer → sorted list of short class names.
    $index = [];
    foreach ($graph as $fqcn => $entry) {
        $index[$entry['layer']][] = $shortNames[$fqcn];
    }
    ksort($index);
    foreach ($index as &$names) {
        sort($names);
    }
    unset($names);

    // Build top-level _instructions index from addCase/addKey attribute arguments.
    $instructions = [];
    foreach ($graph as $fqcn => $entry) {
        $short = $shortNames[$fqcn];
        foreach ($entry['attributes'] ?? [] as $attrName => $attrArgs) {
            if (! is_array($attrArgs)) {
                continue;
            }
            if (isset($attrArgs['addCase']) && $attrArgs['addCase'] !== '') {
                $instructions[$short] = ['type' => 'addCase', 'attribute' => $attrName, 'steps' => $attrArgs['addCase']];
            }
            if (isset($attrArgs['addKey']) && $attrArgs['addKey'] !== '') {
                $instructions[$short] = ['type' => 'addKey', 'attribute' => $attrName, 'steps' => $attrArgs['addKey']];
            }
        }
    }
    ksort($instructions);

    $output_data = ['_index' => $index, '_instructions' => $instructions, '_dependents' => $dependents, 'edges' => $yamlEdges, 'nodes' => $graph];
    $result = Yaml::dump($output_data, 6, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
} elseif ($format === 'json') {
    $jsonEdges = array_map(static function (array $e): array {
        $entry = ['from' => $e['from'], 'to' => $e['to'], 'rel' => $e['rel']];
        if ($e['kind'] !== null) {
            $entry['kind'] = $e['kind'];
        }
        if ($e['source'] !== 'class') {
            $entry['source'] = $e['source'];
        }
        if ($e['args'] !== []) {
            $entry['args'] = $e['args'];
        }
        if ($e['from_file'] !== null) {
            $entry['from_file'] = $e['from_file'];
        }
        if ($e['to_file'] !== null) {
            $entry['to_file'] = $e['to_file'];
        }
        return $entry;
    }, $edges);

    $index = [];
    foreach ($graph as $fqcn => $entry) {
        $index[$entry['layer']][] = $shortNames[$fqcn];
    }
    ksort($index);
    foreach ($index as &$names) {
        sort($names);
    }
    unset($names);

    $instructions = [];
    foreach ($graph as $fqcn => $entry) {
        $short = $shortNames[$fqcn];
        foreach ($entry['attributes'] ?? [] as $attrName => $attrArgs) {
            if (! is_array($attrArgs)) {
                continue;
            }
            if (isset($attrArgs['addCase']) && $attrArgs['addCase'] !== '') {
                $instructions[$short] = ['type' => 'addCase', 'attribute' => $attrName, 'steps' => $attrArgs['addCase']];
            }
            if (isset($attrArgs['addKey']) && $attrArgs['addKey'] !== '') {
                $instructions[$short] = ['type' => 'addKey', 'attribute' => $attrName, 'steps' => $attrArgs['addKey']];
            }
        }
    }
    ksort($instructions);

    $output_data = ['_index' => $index, '_instructions' => $instructions, '_dependents' => $dependents, 'edges' => $jsonEdges, 'nodes' => $graph];
    $result = json_encode($output_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
} elseif ($format === 'markdown') {
    // Markdown readable document.
    $lines = ['# Attribute Graph', ''];

    // Layer index.
    $index = [];
    foreach ($graph as $fqcn => $entry) {
        $index[$entry['layer']][] = $shortNames[$fqcn];
    }
    ksort($index);
    foreach ($index as &$names) {
        sort($names);
    }
    unset($names);

    $lines[] = '## Index';
    $lines[] = '';
    $lines[] = '| Layer | Nodes |';
    $lines[] = '|-------|-------|';
    foreach ($index as $layer => $names) {
        $lines[] = '| ' . $layer . ' | ' . implode(', ', array_map(static fn(string $n): string => '`' . $n . '`', $names)) . ' |';
    }
    $lines[] = '';

    // Instructions.
    $instructions = [];
    foreach ($graph as $fqcn => $entry) {
        $short = $shortNames[$fqcn];
        foreach ($entry['attributes'] ?? [] as $attrName => $attrArgs) {
            if (! is_array($attrArgs)) {
                continue;
            }
            if (isset($attrArgs['addCase']) && $attrArgs['addCase'] !== '') {
                $instructions[$short] = ['type' => 'addCase', 'attribute' => $attrName, 'steps' => $attrArgs['addCase']];
            }
            if (isset($attrArgs['addKey']) && $attrArgs['addKey'] !== '') {
                $instructions[$short] = ['type' => 'addKey', 'attribute' => $attrName, 'steps' => $attrArgs['addKey']];
            }
        }
    }
    ksort($instructions);

    if ($instructions !== []) {
        $lines[] = '## Mutation Instructions';
        $lines[] = '';
        foreach ($instructions as $node => $instr) {
            $lines[] = '### `' . $node . '` (' . $instr['type'] . ' via ' . $instr['attribute'] . ')';
            $lines[] = '';
            foreach (preg_split('/\r?\n/', $instr['steps']) as $step) {
                $lines[] = rtrim($step);
            }
            $lines[] = '';
        }
    }

    // Nodes grouped by layer.
    $lines[] = '## Nodes';
    $lines[] = '';

    $byLayer = [];
    foreach ($graph as $fqcn => $entry) {
        $byLayer[$entry['layer']][$fqcn] = $entry;
    }
    ksort($byLayer);

    foreach ($byLayer as $layer => $layerNodes) {
        $lines[] = '### ' . ucfirst($layer);
        $lines[] = '';
        foreach ($layerNodes as $fqcn => $entry) {
            $short = $shortNames[$fqcn];
            $lines[] = '#### `' . $short . '` (' . $entry['kind'] . ')';
            $lines[] = '';
            $lines[] = '- **File:** `' . $entry['file'] . '`';

            // Class-level attributes.
            if (isset($entry['attributes'])) {
                foreach ($entry['attributes'] as $attrName => $attrArgs) {
                    if ($attrArgs === true) {
                        $lines[] = '- **' . $attrName . '**';
                    } else {
                        $lines[] = '- **' . $attrName . ':** ' . mdFormatArgs($attrArgs);
                    }
                }
            }
            $lines[] = '';

            // Properties.
            if (isset($entry['properties'])) {
                $lines[] = '**Properties:**';
                $lines[] = '';
                $lines[] = '| Property | Attributes |';
                $lines[] = '|----------|------------|';
                foreach ($entry['properties'] as $propName => $propData) {
                    $attrParts = [];
                    foreach ($propData['attributes'] ?? [] as $attrName => $attrArgs) {
                        if ($attrArgs === true) {
                            $attrParts[] = $attrName;
                        } else {
                            $attrParts[] = $attrName . ': ' . mdFormatArgs($attrArgs);
                        }
                    }
                    $lines[] = '| `' . $propName . '` | ' . implode('; ', $attrParts) . ' |';
                }
                $lines[] = '';
            }

            // Methods.
            if (isset($entry['methods'])) {
                $lines[] = '**Methods:**';
                $lines[] = '';
                $lines[] = '| Method | Attributes |';
                $lines[] = '|--------|------------|';
                foreach ($entry['methods'] as $methodName => $methodData) {
                    $attrParts = [];
                    foreach ($methodData['attributes'] ?? [] as $attrName => $attrArgs) {
                        if ($attrArgs === true) {
                            $attrParts[] = $attrName;
                        } else {
                            $attrParts[] = $attrName . ': ' . mdFormatArgs($attrArgs);
                        }
                    }
                    $lines[] = '| `' . $methodName . '()` | ' . implode('; ', $attrParts) . ' |';
                }
                $lines[] = '';
            }

            // Enum cases.
            if (isset($entry['cases'])) {
                $lines[] = '**Cases:**';
                $lines[] = '';
                $hasCaseAttrs = false;
                foreach ($entry['cases'] as $caseData) {
                    if (isset($caseData['attributes'])) {
                        $hasCaseAttrs = true;
                        break;
                    }
                }
                if ($hasCaseAttrs) {
                    $lines[] = '| Case | Value | Attributes |';
                    $lines[] = '|------|-------|------------|';
                    foreach ($entry['cases'] as $caseName => $caseData) {
                        $val = isset($caseData['value']) ? '`' . $caseData['value'] . '`' : '';
                        $attrParts = [];
                        foreach ($caseData['attributes'] ?? [] as $attrName => $attrArgs) {
                            if ($attrArgs === true) {
                                $attrParts[] = $attrName;
                            } else {
                                $attrParts[] = $attrName . ': ' . mdFormatArgs($attrArgs);
                            }
                        }
                        $lines[] = '| `' . $caseName . '` | ' . $val . ' | ' . implode('; ', $attrParts) . ' |';
                    }
                } else {
                    $caseNames = array_keys($entry['cases']);
                    $caseValues = array_map(static fn(array $c): string => isset($c['value']) ? '`' . $c['value'] . '`' : '', $entry['cases']);
                    $lines[] = '| Case | Value |';
                    $lines[] = '|------|-------|';
                    foreach ($entry['cases'] as $caseName => $caseData) {
                        $val = isset($caseData['value']) ? '`' . $caseData['value'] . '`' : '';
                        $lines[] = '| `' . $caseName . '` | ' . $val . ' |';
                    }
                }
                $lines[] = '';
            }

            // Constants.
            if (isset($entry['constants'])) {
                $lines[] = '**Constants:**';
                $lines[] = '';
                $lines[] = '| Constant | Attributes |';
                $lines[] = '|----------|------------|';
                foreach ($entry['constants'] as $constName => $constData) {
                    $attrParts = [];
                    foreach ($constData['attributes'] ?? [] as $attrName => $attrArgs) {
                        if ($attrArgs === true) {
                            $attrParts[] = $attrName;
                        } else {
                            $attrParts[] = $attrName . ': ' . mdFormatArgs($attrArgs);
                        }
                    }
                    $lines[] = '| `' . $constName . '` | ' . implode('; ', $attrParts) . ' |';
                }
                $lines[] = '';
            }
        }
    }

    // Edges table.
    $lines[] = '## Edges';
    $lines[] = '';
    $lines[] = '| From | To | Relationship | Kind | Source |';
    $lines[] = '|------|----|-------------|------|--------|';
    foreach ($edges as $edge) {
        $source = $edge['source'] === 'class' ? '' : $edge['source'];
        $kind = $edge['kind'] ?? '';
        $lines[] = '| `' . $edge['from'] . '` | `' . $edge['to'] . '` | ' . $edge['rel'] . ' | ' . $kind . ' | ' . $source . ' |';
    }
    $lines[] = '';

    // Dependents (reverse-edge index).
    if ($dependents !== []) {
        $lines[] = '## Dependents';
        $lines[] = '';
        foreach ($dependents as $target => $deps) {
            $lines[] = '### `' . $target . '`';
            $lines[] = '';
            foreach ($deps as $dep) {
                $label = '`' . $dep['from'] . '` via ' . $dep['rel'];
                if (isset($dep['source'])) {
                    $label .= ' (' . $dep['source'] . ')';
                }
                $lines[] = '- ' . $label;
            }
            $lines[] = '';
        }
    }

    $result = implode("\n", $lines) . "\n";
} else {
    // Mermaid class diagram.
    $lines = ['classDiagram'];

    foreach ($graph as $fqcn => $entry) {
        $short = $shortNames[$fqcn];
        $stereotype = $entry['kind'];
        $lines[] = "    class $short {";
        $lines[] = "        <<$stereotype>>";

        // Class-level attributes.
        foreach ($entry['attributes'] ?? [] as $attrName => $attrArgs) {
            $lines[] = '        +' . formatMermaidAttr($attrName, $attrArgs);
        }

        // Properties with their attributes.
        foreach ($entry['properties'] ?? [] as $propName => $propData) {
            foreach ($propData['attributes'] ?? [] as $attrName => $attrArgs) {
                $lines[] = '        ' . sanitizeMermaid($propName) . ' : ' . formatMermaidAttr($attrName, $attrArgs);
            }
        }

        // Methods with their attributes.
        foreach ($entry['methods'] ?? [] as $methodName => $methodData) {
            foreach ($methodData['attributes'] ?? [] as $attrName => $attrArgs) {
                $lines[] = '        ' . sanitizeMermaid($methodName) . '() : ' . formatMermaidAttr($attrName, $attrArgs);
            }
        }

        // Enum cases / constants with their attributes.
        foreach (['cases', 'constants'] as $section) {
            foreach ($entry[$section] ?? [] as $caseName => $caseData) {
                foreach ($caseData['attributes'] ?? [] as $attrName => $attrArgs) {
                    $lines[] = '        ' . sanitizeMermaid($caseName) . ' : ' . formatMermaidAttr($attrName, $attrArgs);
                }
            }
        }

        $lines[] = '    }';
    }

    // Group edges by from|to|rel and aggregate sources.
    $edgeGroups = [];
    foreach ($edges as $edge) {
        $key = $edge['from'] . '|' . $edge['to'] . '|' . $edge['rel'];
        $edgeGroups[$key] ??= ['from' => $edge['from'], 'to' => $edge['to'], 'rel' => $edge['rel'], 'kind' => $edge['kind'], 'sources' => [], 'from_file' => $edge['from_file'], 'to_file' => $edge['to_file']];
        $edgeGroups[$key]['sources'][] = $edge['source'];
    }
    foreach ($edgeGroups as $group) {
        $label = $group['rel'];
        if ($group['kind'] !== null) {
            $label = '(' . $group['kind'] . ') ' . $label;
        }
        // Annotate with source locations when not all class-level.
        $nonClass = array_filter($group['sources'], static fn(string $s): bool => $s !== 'class');
        if ($nonClass !== []) {
            $shortSources = array_map(static function (string $s): string {
                // "case:home" → "home", "property:id" → "id", "method:boot" → "boot()"
                $parts = explode(':', $s, 2);
                return match ($parts[0]) {
                    'method' => $parts[1] . '()',
                    default => $parts[1] ?? $s,
                };
            }, array_unique($nonClass));
            $sourceList = implode(', ', $shortSources);
            if (strlen($sourceList) > 40) {
                $sourceList = substr($sourceList, 0, 37) . '...';
            }
            $label .= ' via ' . sanitizeMermaid($sourceList);
        }
        $fileParts = array_filter([
            $group['from_file'] !== null ? $group['from_file'] : null,
            $group['to_file'] !== null ? $group['to_file'] : null,
        ]);
        $fileComment = $fileParts !== [] ? ' %% ' . implode(' → ', $fileParts) : '';
        $lines[] = "    {$group['from']} ..> {$group['to']} : {$label}{$fileComment}";
    }

    // Dependents as reverse-edge notes on each target node.
    foreach ($dependents as $target => $deps) {
        $depLabels = array_map(static function (array $d): string {
            $label = $d['from'] . ' via ' . $d['rel'];
            if (isset($d['source'])) {
                $label .= ' (' . $d['source'] . ')';
            }
            return sanitizeMermaid($label);
        }, $deps);
        $lines[] = '    note for ' . $target . ' "Dependents: ' . implode(', ', $depLabels) . '"';
    }

    $result = implode("\n", $lines) . "\n";
}

/** Format attribute arguments as an inline markdown string. */
function mdFormatArgs(mixed $args): string
{
    if ($args === true) {
        return 'true';
    }
    if (is_scalar($args)) {
        return '`' . $args . '`';
    }
    if (is_array($args)) {
        $parts = [];
        foreach ($args as $k => $v) {
            $formatted = is_array($v) ? summarizeArgs($v) : (string) ($v === true ? 'true' : ($v === false ? 'false' : ($v === null ? 'null' : $v)));
            $parts[] = is_string($k) ? $k . ': ' . $formatted : $formatted;
        }
        return implode(', ', $parts);
    }
    return '';
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
