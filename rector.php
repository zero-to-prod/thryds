<?php

declare(strict_types=1);

use League\Route\Router;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Utils\Rector\Rector\AddNamedArgWhenVarMismatchesParamRector;
use Utils\Rector\Rector\AddViewKeyConstantRector;
use Utils\Rector\Rector\ExtractRepeatedExpressionToVariableRector;
use Utils\Rector\Rector\ExtractRoutePatternToRouteClassRector;
use Utils\Rector\Rector\ForbidArrayShapeReturnRector;
use Utils\Rector\Rector\ForbidBareGetenvCallRector;
use Utils\Rector\Rector\ForbidBareServerEnvKeyRector;
use Utils\Rector\Rector\ForbidEnvCheckOutsideConfigRector;
use Utils\Rector\Rector\ForbidCallableTypeVariableNameRector;
use Utils\Rector\Rector\ForbidDeepNestingRector;
use Utils\Rector\Rector\ForbiddenFuncCallRector;
use Utils\Rector\Rector\ForbidDirectRouterInstantiationRector;
use Utils\Rector\Rector\ForbidDuplicateRoutePatternRector;
use Utils\Rector\Rector\ForbidDuplicateRouteRegistrationRector;
use Utils\Rector\Rector\ForbidDynamicIncludeRector;
use Utils\Rector\Rector\ForbidErrorSuppressionRector;
use Utils\Rector\Rector\ForbidEvalRector;
use Utils\Rector\Rector\ForbidExitInSourceRector;
use Utils\Rector\Rector\ForbidGlobalKeywordRector;
use Utils\Rector\Rector\ForbidHardcodedRouteStringRector;
use Utils\Rector\Rector\ForbidLongClosureRector;
use Utils\Rector\Rector\ForbidMagicHttpStatusCodeRector;
use Utils\Rector\Rector\ForbidMagicStringArrayKeyRector;
use Utils\Rector\Rector\ForbidStringArgForEnumParamRector;
use Utils\Rector\Rector\ForbidStringComparisonOnEnumPropertyRector;
use Utils\Rector\Rector\ForbidStringRoutePatternRector;
use Utils\Rector\Rector\ForbidVariableVariablesRector;
use Utils\Rector\Rector\FrankenPhpLogToLogClassRector;
use Utils\Rector\Rector\InlineSingleUseVariableRector;
use Utils\Rector\Rector\LimitConstructorParamsRector;
use Utils\Rector\Rector\MakeClassReadonlyRector;
use Utils\Rector\Rector\RemoveNamedArgWhenVarMatchesParamRector;
use Utils\Rector\Rector\RenameEnumCaseToMatchValueRector;
use Utils\Rector\Rector\RenameParamToMatchTypeNameRector;
use Utils\Rector\Rector\RenamePrimitivePropertyToSnakeCaseRector;
use Utils\Rector\Rector\RenamePrimitiveVarToSnakeCaseRector;
use Utils\Rector\Rector\RenamePropertyToMatchTypeNameRector;
use Utils\Rector\Rector\RenameVarToMatchReturnTypeRector;
use Utils\Rector\Rector\ReplaceFullyQualifiedNameRector;
use Utils\Rector\Rector\ReplaceShortClassNameWithViewKeyRector;
use Utils\Rector\Rector\RequireAllRouteCasesRegisteredRector;
use Utils\Rector\Rector\RequireClosedSetOnBackedEnumRector;
use Utils\Rector\Rector\RequireConstForRepeatedArrayKeyRector;
use Utils\Rector\Rector\RequireEnumValueAccessRector;
use Utils\Rector\Rector\RequireFragmentIfForBladeRenderRector;
use Utils\Rector\Rector\RequireLogEventRector;
use Utils\Rector\Rector\RequireDownMigrationRector;
use Utils\Rector\Rector\RequireMethodAnnotationForDataModelRector;
use Utils\Rector\Rector\RequireNamedArgForBoolParamRector;
use Utils\Rector\Rector\RequireNamesKeysOnConstantsClassRector;
use Utils\Rector\Rector\RequireNamesKeysOnMixedConstantsClassRector;
use Utils\Rector\Rector\RequireParamTypeRector;
use Utils\Rector\Rector\RequireReturnTypeRector;
use Utils\Rector\Rector\RequireRouteEnumInMapCallRector;
use Utils\Rector\Rector\RequireRoutePatternConstRector;
use Utils\Rector\Rector\RequireRouteTestRector;
use Utils\Rector\Rector\RequireSpecificResponseReturnTypeRector;
use Utils\Rector\Rector\RequireTypedPropertyRector;
use Utils\Rector\Rector\RequireViewEnumInMakeCallRector;
use Utils\Rector\Rector\RequireViewModelAttributeOnDataModelRector;
use Utils\Rector\Rector\RouteParamNameMustBeConstRector;
use Utils\Rector\Rector\SuggestAttributeForRepeatedPropertyPatternRector;
use Utils\Rector\Rector\SuggestConstArrayToEnumRector;
use Utils\Rector\Rector\SuggestDuplicateStringConstantRector;
use Utils\Rector\Rector\SuggestEnumForKeyRegistryWithMethodsRector;
use Utils\Rector\Rector\SuggestEnumForNameEqualsValueConstRector;
use Utils\Rector\Rector\SuggestEnumForStringPropertyRector;
use Utils\Rector\Rector\StringArgToClassConstRector;
use Utils\Rector\Rector\SuggestExtractSharedCatchLogicRector;
use Utils\Rector\Rector\UseClassConstArrayKeyForDataModelRector;
use Utils\Rector\Rector\UseLogContextConstRector;
use Utils\Rector\Rector\ValidateChecklistPathsRector;
use Utils\Rector\Rector\SuggestEnumForInternalOnlyConstantsRector;
use Utils\Rector\Rector\ForbidCrossFileStringDuplicationRector;
use Utils\Rector\Rector\RequireExhaustiveMatchOnEnumRector;
use Utils\Rector\Rector\RequireEnumForBranchingConstantRector;
use Utils\Rector\Rector\DetectParallelBladePhpBehaviorRector;
use Utils\Rector\Rector\ValidateRequirementIdsRector;
use Utils\Rector\Rector\MigrateAddCaseListToHeredocRector;
use Utils\Rector\Rector\VerticalAttributeArgsRector;
use Utils\Rector\Rector\DetectStaleCodeReferencesRector;
use Utils\Rector\Rector\RemoveDefaultsAndApplyAtCallsiteRector;
use Utils\Rector\Rector\ForbidHardcodedNamespacePrefixRector;
use Utils\Rector\Rector\RouteInfoRequiredRector;
use Utils\Rector\Rector\RouteOperationRequiredRector;
use Utils\Rector\Rector\RequirePersistsOnTableReferenceRector;
use Utils\Rector\Rector\RequireViewModelDataInMakeCallRector;
use Utils\Rector\Rector\RouteOperationRequiresRouteInfoRector;
use Utils\Rector\Rector\AddViewModelAttributeRector;
use Utils\Rector\Rector\RequireViewKeyConstantOnViewModelRector;
use Utils\Rector\Rector\UseColumnConstantsInQueriesRector;
use Utils\Rector\Rector\RequireEnumOrConstInStringComparisonRector;
use Utils\Rector\Rector\RequireHandlesExceptionParamMatchRector;
use Utils\Rector\Rector\RequireHandlesExceptionOnPublicHandlerMethodRector;
use Utils\Rector\Rector\ForbidReflectionInInstanceMethodRector;
use Utils\Rector\Rector\ForbidReflectionInClosureRector;
use Utils\Rector\Rector\ForbidHttpMethodBranchingInControllerRector;
use Utils\Rector\Rector\RequireHandlesRouteAttributeRector;
use Utils\Rector\Rector\EnforceLayerCoverageRector;
use Utils\Rector\Rector\ForbidInterfaceRector;
use Utils\Rector\Rector\ForbidClassInheritanceRector;
use Rector\CodeQuality\Rector\FuncCall\SortCallLikeNamedArgsRector;
use Rector\CodeQuality\Rector\Attribute\SortAttributeNamedArgsRector;
use ZeroToProd\Thryds\Attributes\Requirement;
use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;
use ZeroToProd\Thryds\Env;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\KeyRegistry;
use ZeroToProd\Thryds\Attributes\SourceOfTruth;
use ZeroToProd\Thryds\Attributes\ViewModel;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\UI\AlertVariant;
use ZeroToProd\Thryds\UI\ButtonSize;
use ZeroToProd\Thryds\UI\ButtonVariant;
use ZeroToProd\Thryds\UI\InputType;
use ZeroToProd\Thryds\Log;
use ZeroToProd\Thryds\LogContext;
use ZeroToProd\Thryds\LogLevel;
use ZeroToProd\Thryds\OpcacheStatus;
use ZeroToProd\Thryds\Routes\HttpMethod;
use ZeroToProd\Thryds\Tables\Migration;
use ZeroToProd\Thryds\Tables\User;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/public',
        __DIR__ . '/tests',
        __DIR__ . '/migrations',
    ]);
    // Table definition enums use attributes on enum cases, which triggers a Rector
    // scope-resolution bug (PhpParser\Node\Attribute missing scope). These files are
    // pure schema declarations — no executable logic for Rector to transform.
    $rectorConfig->skip([
        __DIR__ . '/src/Tables',
        // Intentionally minimal fixture files trigger Rector's scope-resolution bug
        __DIR__ . '/tests/Database/Fixtures/MigrationsSkip',
        __DIR__ . '/tests/Database/Fixtures/MigrationsWrongId',
        __DIR__ . '/tests/Database/Fixtures/MigrationsNotInterface',
        // Migration files repeat table names across up()/down() by design;
        // SQL generation layer uses hardcoded SQL type strings as output, not references to enum cases
        SuggestDuplicateStringConstantRector::class => [__DIR__ . '/migrations', __DIR__ . '/src/Schema/Driver.php'],
        DetectParallelBladePhpBehaviorRector::class => [__DIR__ . '/src/Schema/Driver.php', __DIR__ . '/src/Schema/DdlBuilder.php'],
        ForbidCrossFileStringDuplicationRector::class => [__DIR__ . '/src/Schema/Driver.php', __DIR__ . '/src/Schema/DdlBuilder.php'],
    ]);
    $rectorConfig->importNames();

    // --- Naming ---
    $rectorConfig->ruleWithConfiguration(RenameParamToMatchTypeNameRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RenameVarToMatchReturnTypeRector::class, [
        'skipNames' => ['Closure'],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RenameEnumCaseToMatchValueRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RenamePrimitivePropertyToSnakeCaseRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RenamePrimitiveVarToSnakeCaseRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RenamePropertyToMatchTypeNameRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidCallableTypeVariableNameRector::class, [
        'forbiddenNames' => [
            'Closure',
            'Callable',
            'Callback',
            'Function',
            'Func',
        ],
        'mode' => 'warn',
        'message' => 'TODO: [ForbidCallableTypeVariableNameRector] Constants name things — rename $%s to describe its behaviour.',
    ]);

    // --- Type Safety ---
    $rectorConfig->ruleWithConfiguration(RequireReturnTypeRector::class, [
        'skipMagicMethods' => true,
        'skipClosures' => false,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireParamTypeRector::class, [
        'skipVariadic' => true,
        'useDocblocks' => true,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireTypedPropertyRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [RequireTypedPropertyRector] Attributes define properties — add a type declaration for OPcache optimization.',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireNamedArgForBoolParamRector::class, [
        'skipBuiltinFunctions' => false,
        'skipWhenOnlyArg' => true,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(AddNamedArgWhenVarMismatchesParamRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RemoveNamedArgWhenVarMatchesParamRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->rule(SortCallLikeNamedArgsRector::class);
    $rectorConfig->rule(SortAttributeNamedArgsRector::class);

    // --- Code Quality ---
    $rectorConfig->ruleWithConfiguration(MakeClassReadonlyRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->rule(RemoveUnusedPrivateMethodParameterRector::class);
    $rectorConfig->rule(RemoveUnusedPublicMethodParameterRector::class);
    $rectorConfig->ruleWithConfiguration(ForbidLongClosureRector::class, [
        'maxStatements' => 5,
        'skipArrowFunctions' => true,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDeepNestingRector::class, [
        'maxDepth' => 3,
        'maxNegationComplexity' => 2,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(LimitConstructorParamsRector::class, [
        'maxParams' => 10,
        'dtoSuffix' => 'Deps',
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidArrayShapeReturnRector::class, [
        'minKeys' => 2,
        'classSuffix' => 'Result',
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ExtractRepeatedExpressionToVariableRector::class, [
        'functions' => ['dirname'],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(InlineSingleUseVariableRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(StringArgToClassConstRector::class, [
        'mappings' => [],
        'mode' => 'auto',
    ]);
    // Auto-discovers all #[Attribute] classes with defaulted params and inlines
    // those defaults at every call site. Functions/methods require explicit opt-in
    // via targetFunctions/targetMethods. See: utils/rector/docs/RemoveDefaultsAndApplyAtCallsiteRector.md
    $rectorConfig->ruleWithConfiguration(RemoveDefaultsAndApplyAtCallsiteRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestExtractSharedCatchLogicRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestExtractSharedCatchLogicRector] Multiple catch blocks instantiate the same classes (%s) — extract shared logic to a named, reusable declaration.',
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestDuplicateStringConstantRector::class, [
        'mode' => 'warn',
        'message' => "TODO: [SuggestDuplicateStringConstantRector] Constants name things — duplicate string '%s' (used %dx). Extract to a single source of truth.",
        'ignoredAttributeClasses' => [
            Requirement::class,
        ],
        'ignoredValues' => [
            ', :',
        ],
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestConstArrayToEnumRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestConstArrayToEnumRector] Enumerations define sets — migrate const arrays to a backed enum with #[ClosedSet] and #[Group] attributes.',
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestEnumForStringPropertyRector::class, [
        'dataModelTraits' => [
            DataModel::class,
            \ZeroToProd\Thryds\Attributes\DataModel::class,
        ],
        'describeAttrs' => [
            Describe::class,
            \ZeroToProd\Thryds\Attributes\Describe::class,
        ],
        'excludedFiles' => ['View.php'],
        'mode' => 'warn',
        'message' => 'TODO: [SuggestEnumForStringPropertyRector] Enumerations define sets — $%s has values: %s. Extract to a backed enum with #[ClosedSet].',
        'callSiteMessage' => 'TODO: [SuggestEnumForStringPropertyRector] Enumerations define sets — %s is a value of %s::$%s. Replace with an enum case.',
    ]);

    // --- Magic String Elimination ---
    $rectorConfig->rule(StringClassNameToClassConstantRector::class);
    $rectorConfig->ruleWithConfiguration(ForbidMagicStringArrayKeyRector::class, [
        'excludedClasses' => [Log::class],
        'mode' => 'warn',
        'message' => "TODO: [ForbidMagicStringArrayKeyRector] Constants name things — define a public const with value '%s' on the appropriate class.",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireEnumOrConstInStringComparisonRector::class, [
        'mode' => 'warn',
        'message' => "TODO: [RequireEnumOrConstInStringComparisonRector] Constants name things, enumerations define sets — raw string '%s' in comparison must be backed by an enum or constant.",
    ]);

    // --- Forbidden Constructs ---
    $rectorConfig->ruleWithConfiguration(ForbiddenFuncCallRector::class, [
        'functions' => [
            'error_log',
            'extract',
            'compact',
            'session_start',
        ],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidEvalRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidExitInSourceRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidMagicHttpStatusCodeRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidMagicHttpStatusCodeRector] Constants name things — replace %d with a named HTTP status constant or enum case.',
    ]);

    // --- OPcache Optimization ---
    $rectorConfig->ruleWithConfiguration(ForbidDynamicIncludeRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidDynamicIncludeRector] Declarative imports only — dynamic include prevents compile-time optimization.',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidVariableVariablesRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidVariableVariablesRector] Declarations must be static — variable variables prevent compile-time resolution.',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidErrorSuppressionRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidErrorSuppressionRector] Handle errors explicitly — @ error suppression adds per-call overhead.',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidGlobalKeywordRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidGlobalKeywordRector] Declarations must be scoped — global keyword prevents scope-level optimization.',
    ]);

    // --- Logging ---
    $rectorConfig->ruleWithConfiguration(FrankenPhpLogToLogClassRector::class, [
        'functions' => ['frankenphp_log'],
        'logClass' => Log::class,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireLogEventRector::class, [
        'logClass' => Log::class,
        'logContextClass' => LogContext::class,
        'eventKey' => 'event',
        'mode' => 'warn',
        'message' => 'TODO: [RequireLogEventRector] Constants name things — add a durable event identifier `%s::%s => %s::<event_label>` to the context array.',
    ]);
    $rectorConfig->ruleWithConfiguration(UseLogContextConstRector::class, [
        'logClass' => Log::class,
        'logContextClass' => LogContext::class,
        'keys' => ['exception', 'file', 'line'],
        'mode' => 'auto',
    ]);

    // --- Controller Conventions ---
    $rectorConfig->ruleWithConfiguration(RequireSpecificResponseReturnTypeRector::class, [
        'controllerNamespaces' => ['ZeroToProd\Thryds\Controllers'],
        'genericInterface' => 'Psr\Http\Message\ResponseInterface',
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidHttpMethodBranchingInControllerRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidHttpMethodBranchingInControllerRector] Controllers must not branch on HTTP method — declare separate #[RouteOperation] handler methods and let the router dispatch. See: utils/rector/docs/ForbidHttpMethodBranchingInControllerRector.md',
    ]);

    // --- DataModel & ViewModel ---
    $rectorConfig->ruleWithConfiguration(AddViewModelAttributeRector::class, [
        'namespace' => 'ZeroToProd\\Thryds\\ViewModels',
        'attributeClass' => ViewModel::class,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireViewModelAttributeOnDataModelRector::class, [
        'traitClasses' => [
            \ZeroToProd\Thryds\Attributes\DataModel::class,
            DataModel::class,
        ],
        'constantName' => 'view_key',
        'attributeClass' => ViewModel::class,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestAttributeForRepeatedPropertyPatternRector::class, [
        'patterns' => [
            [
                'trait' => \ZeroToProd\Thryds\Attributes\DataModel::class,
                'constant' => 'view_key',
                'attribute' => ViewModel::class,
            ],
        ],
        'mode' => 'auto',
        'message' => 'TODO: [SuggestAttributeForRepeatedPropertyPatternRector] Attributes define properties — %s uses %s + %s. Add #[%s] attribute.',
    ]);
    $rectorConfig->ruleWithConfiguration(UseClassConstArrayKeyForDataModelRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireViewKeyConstantOnViewModelRector::class, [
        'viewModelAttribute' => ViewModel::class,
        'mode' => 'warn',
        'message' => 'TODO: [RequireViewKeyConstantOnViewModelRector] Attributes define properties — %s is missing `public const string view_key`. Required for graph inventory.',
    ]);
    $rectorConfig->ruleWithConfiguration(AddViewKeyConstantRector::class, [
        'dataModelTraits' => [
            \ZeroToProd\Thryds\Attributes\DataModel::class,
        ],
        'viewModelAttribute' => ViewModel::class,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ReplaceShortClassNameWithViewKeyRector::class, [
        'shortClassNameFunction' => 'short_class_name',
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireMethodAnnotationForDataModelRector::class, [
        'dataModelTraits' => [
            DataModel::class,
            \ZeroToProd\Thryds\Attributes\DataModel::class,
        ],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ReplaceFullyQualifiedNameRector::class, [
        'replacements' => [
            DataModel::class => \ZeroToProd\Thryds\Attributes\DataModel::class,
            Describe::class => \ZeroToProd\Thryds\Attributes\Describe::class,
        ],
        'mode' => 'auto',
    ]);

    $rectorConfig->ruleWithConfiguration(RequireViewModelDataInMakeCallRector::class, [
        'viewEnumClass'       => View::class,
        'viewModelsNamespace' => 'ZeroToProd\\Thryds\\ViewModels',
        'mode'                => 'warn',
        'message'             => "TODO: [RequireViewModelDataInMakeCallRector] Attributes define properties — make() renders '%s' which has a %s. Pass data: [%s::view_key => %s::from([...])] so the view receives typed context.",
    ]);

    // --- Route Safety ---
    $rectorConfig->ruleWithConfiguration(ForbidDirectRouterInstantiationRector::class, [
        'forbiddenClasses' => [Router::class],
        'mode' => 'warn',
        'message' => 'TODO: [ForbidDirectRouterInstantiationRector] Use League\\Route\\Cache\\Router instead of instantiating %s directly — direct instantiation bypasses route caching.',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidStringRoutePatternRector::class, [
        'methods' => ['map'],
        'argPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [ForbidStringRoutePatternRector] Enumerations define sets — replace inline string '%s' with a Route enum case reference (e.g. Route::case->value).",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireRouteEnumInMapCallRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'methods' => ['map'],
        'argPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [RequireRouteEnumInMapCallRector] Enumerations define sets — route pattern must use Route::case->value. Found '%s' instead.",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireViewEnumInMakeCallRector::class, [
        'enumClass' => View::class,
        'methodName' => 'make',
        'paramName' => 'view',
        'mode' => 'auto',
        'message' => "TODO: [RequireViewEnumInMakeCallRector] Enumerations define sets — use View::%s->value instead of string '%s'.",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidHardcodedRouteStringRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'mode' => 'warn',
        'message' => "TODO: [ForbidHardcodedRouteStringRector] Enumerations define sets — use Route::%s->value instead of hardcoded '%s'.",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDuplicateRouteRegistrationRector::class, [
        'methods' => ['map'],
        'methodArgPosition' => 0,
        'routeArgPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [ForbidDuplicateRouteRegistrationRector] Each route declares once — duplicate registration: '%s %s' was already registered above.",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireAllRouteCasesRegisteredRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'methods' => ['map'],
        'argPosition' => 1,
        'scanDir' => __DIR__ . '/src',
        'mode' => 'warn',
        'message' => "TODO: [RequireAllRouteCasesRegisteredRector] Enumerations define sets — route case '%s' is defined but never registered in any router map() call.",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireRouteTestRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'testDir' => __DIR__ . '/tests',
        'mode' => 'warn',
        'message' => "TODO: [RequireRouteTestRector] Route case '%s' has no corresponding test — add a test that exercises this route.",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireRoutePatternConstRector::class, [
        'classSuffix' => 'Route',
        'constName' => 'pattern',
        'excludedClasses' => ['ZeroToProd\Thryds\Attributes\CoversRoute', 'ZeroToProd\Thryds\Attributes\HandlesRoute'],
        'mode' => 'warn',
        'message' => "TODO: [RequireRoutePatternConstRector] Constants name things — route class '%s' is missing a '%s' constant. Define: public const string %s = '/...'.",
    ]);
    $rectorConfig->ruleWithConfiguration(RouteParamNameMustBeConstRector::class, [
        'classSuffix' => 'Route',
        'constName' => 'pattern',
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDuplicateRoutePatternRector::class, [
        'classSuffix' => 'Route',
        'constNames' => ['pattern'],
        'scanDir' => __DIR__ . '/src',
        'mode' => 'warn',
        'message' => "TODO: [ForbidDuplicateRoutePatternRector] Each route declares once — duplicate pattern '%s' already defined in %s::%s.",
    ]);
    $rectorConfig->ruleWithConfiguration(ExtractRoutePatternToRouteClassRector::class, [
        'methods' => ['map'],
        'argPosition' => 1,
        'namespace' => 'ZeroToProd\\Thryds\\Routes',
        'outputDir' => __DIR__ . '/src/Routes',
        'mode' => 'warn',
        'message' => 'TODO: [ExtractRoutePatternToRouteClassRector] Constants name things — extract inline route string to a Route class constant.',
    ]);

    // --- Environment Key Safety ---
    $rectorConfig->ruleWithConfiguration(ForbidEnvCheckOutsideConfigRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidEnvCheckOutsideConfigRector] Attributes define properties — direct env read outside Config boundary. Move to AppEnv or Config class.',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidBareServerEnvKeyRector::class, [
        'envClass' => Env::class,
        'superglobals' => ['_SERVER', '_ENV'],
        'mode' => 'auto',
        'message' => "TODO: [ForbidBareServerEnvKeyRector] Constants name things — use %s::%s instead of bare string '%s'.",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidBareGetenvCallRector::class, [
        'envClass' => Env::class,
        'functions' => ['getenv'],
        'mode' => 'auto',
        'message' => "TODO: [ForbidBareGetenvCallRector] Constants name things — use %s::%s instead of bare string '%s' in getenv().",
    ]);

    // --- Enum Value Safety ---
    $rectorConfig->ruleWithConfiguration(RequireEnumValueAccessRector::class, [
        'enumClasses' => [
            View::class,
            \ZeroToProd\Thryds\Routes\Route::class,
            HttpMethod::class,
            \ZeroToProd\Thryds\AppEnv::class,
            LogLevel::class,
        ],
        'mode' => 'auto',
        'message' => 'TODO: [RequireEnumValueAccessRector] Enumerations define sets — %s::%s is a backed enum case. Use ->value to get the string.',
    ]);

    $rectorConfig->ruleWithConfiguration(ForbidStringComparisonOnEnumPropertyRector::class, [
        'enumClasses' => [
            \ZeroToProd\Thryds\AppEnv::class,
            \ZeroToProd\Thryds\Routes\Route::class,
            HttpMethod::class,
            LogLevel::class,
            View::class,
        ],
        'mode' => 'warn',
        'message' => "TODO: [ForbidStringComparisonOnEnumPropertyRector] Enumerations define sets — compare against %s::%s instead of string '%s'.",
    ]);

    // --- Constants Class Design ---
    $rectorConfig->ruleWithConfiguration(RequireNamesKeysOnConstantsClassRector::class, [
        'attributeClass' => KeyRegistry::class,
        'excludedAttributes' => [
            \Attribute::class,
            ViewModel::class,
        ],
        'mode' => 'warn',
        'message' => 'TODO: [RequireNamesKeysOnConstantsClassRector] Constants name things — %s contains only string constants. Add #[KeyRegistry] to declare what they name.',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireNamesKeysOnMixedConstantsClassRector::class, [
        'attributeClass' => KeyRegistry::class,
        'minConstants' => 3,
        'excludedTraits' => [
            \ZeroToProd\Thryds\Attributes\DataModel::class,
            DataModel::class,
        ],
        'excludedAttributes' => [
            ViewModel::class,
        ],
        'mode' => 'warn',
        'message' => 'TODO: [RequireNamesKeysOnMixedConstantsClassRector] Constants name things — %s has %d string constants. Add #[KeyRegistry] to declare what they name.',
    ]);

    // --- Enum Design ---
    $rectorConfig->ruleWithConfiguration(RequireClosedSetOnBackedEnumRector::class, [
        'attributeClass' => ClosedSet::class,
        'mode' => 'warn',
        'message' => 'TODO: [RequireClosedSetOnBackedEnumRector] Enumerations define sets — backed enum %s must declare #[ClosedSet].',
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestEnumForNameEqualsValueConstRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestEnumForNameEqualsValueConstRector] Enumerations define sets — %s has %d string constants where name equals value. Migrate to a backed enum with #[ClosedSet].',
        'minConstants' => 2,
        'excludedAttributes' => [
            ClosedSet::class,
            KeyRegistry::class,
        ],
    ]);
    $rectorConfig->ruleWithConfiguration(RequireExhaustiveMatchOnEnumRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [RequireExhaustiveMatchOnEnumRector] Enumerations define sets — match() on %s must cover all cases explicitly.',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireEnumForBranchingConstantRector::class, [
        'mode' => 'warn',
        'minCases' => 3,
        'message' => 'TODO: [RequireEnumForBranchingConstantRector] Enumerations define sets — $%s is compared against %d literals (%s), forming an implicit closed set. Extract to a backed enum with #[ClosedSet] and use match().',
    ]);
    // --- Enum Value Arg Safety ---
    $rectorConfig->ruleWithConfiguration(ForbidStringArgForEnumParamRector::class, [
        'enumClasses' => [
            \ZeroToProd\Thryds\AppEnv::class,
            HttpMethod::class,
            \ZeroToProd\Thryds\Routes\Route::class,
            View::class,
            LogLevel::class,
            ButtonVariant::class,
            ButtonSize::class,
            AlertVariant::class,
            InputType::class,
        ],
        'mode' => 'warn',
        'message' => "TODO: [ForbidStringArgForEnumParamRector] Enumerations define sets — '%s' matches %s::%s. Use %s::%s->value.",
    ]);

    $rectorConfig->ruleWithConfiguration(RequireConstForRepeatedArrayKeyRector::class, [
        'minOccurrences' => 2,
        'minLength' => 3,
        'excludedKeys' => ['class', 'mode', 'message'],
        'excludedClasses' => [LogContext::class, OpcacheStatus::class],
        'mode' => 'warn',
        'message' => "TODO: [RequireConstForRepeatedArrayKeyRector] Constants name things — '%s' used %dx as array key. Extract to a class constant.",
    ]);


    // --- Blade / htmx ---
    $rectorConfig->ruleWithConfiguration(RequireFragmentIfForBladeRenderRector::class, [
        'mode' => 'warn',
        'message' => "TODO: [RequireFragmentIfForBladeRenderRector] ->render() returns the full page — for htmx partial requests, use ->fragmentIf(\$request->hasHeader(Header::hx_request), 'body') instead.",
    ]);

    // --- Checklist Validation ---
    $rectorConfig->ruleWithConfiguration(MigrateAddCaseListToHeredocRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(VerticalAttributeArgsRector::class, [
        'minArgs' => 2,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ValidateChecklistPathsRector::class, [
        'attributes' => [
            ['attributeClass' => SourceOfTruth::class, 'paramName' => 'addCase'],
            ['attributeClass' => ClosedSet::class, 'paramName' => 'addCase'],
            ['attributeClass' => KeyRegistry::class, 'paramName' => 'addKey'],
        ],
        'projectDir' => __DIR__,
        'mode' => 'warn',
        'message' => "TODO: [ValidateChecklistPathsRector] Checklists must be accurate — %s references '%s' in %s, but this file does not exist. Update the checklist.",
    ]);

    $rectorConfig->ruleWithConfiguration(SuggestEnumForKeyRegistryWithMethodsRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestEnumForKeyRegistryWithMethodsRector] Enumerations define sets, attributes define properties — %s has #[KeyRegistry] but also contains methods. Extract constants to a backed enum with #[ClosedSet].',
    ]);

    $rectorConfig->ruleWithConfiguration(SuggestEnumForInternalOnlyConstantsRector::class, [
        'mode' => 'warn',
        'excludedAttributes' => [
            KeyRegistry::class,
        ],
        'message' => 'TODO: [SuggestEnumForInternalOnlyConstantsRector] Enumerations define sets — %s has %d string constants only referenced internally. Migrate to a backed enum with #[ClosedSet].',
    ]);

    $rectorConfig->ruleWithConfiguration(ForbidCrossFileStringDuplicationRector::class, [
        'mode' => 'warn',
        'minFiles' => 3,
        'message' => "TODO: [ForbidCrossFileStringDuplicationRector] Constants name things — string '%s' appears in %d files. Extract to a shared constant.",
    ]);

    $rectorConfig->ruleWithConfiguration(DetectParallelBladePhpBehaviorRector::class, [
        'mode' => 'warn',
        'message' => "TODO: [DetectParallelBladePhpBehaviorRector] Enumerations define sets — use %s::%s instead of hardcoded '%s'.",
    ]);

    // --- Migrations ---
    $rectorConfig->ruleWithConfiguration(RequireDownMigrationRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [RequireDownMigrationRector] Migration class is missing a down() method — add it to support rollback.',
    ]);

    // --- Requirement Tracing ---
    $rectorConfig->ruleWithConfiguration(ValidateRequirementIdsRector::class, [
        'requirements_file' => __DIR__ . '/requirements.yaml',
        'message' => "TODO: [ValidateRequirementIdsRector] Attributes define properties — requirement ID '%s' not found in requirements.yaml.",
    ]);

    $rectorConfig->ruleWithConfiguration(DetectStaleCodeReferencesRector::class, [
        'mode' => 'warn',
        'message' => "TODO: [DetectStaleCodeReferencesRector] Comments must be evergreen — references '%s' which does not exist. Verify or remove.",
    ]);


    $rectorConfig->ruleWithConfiguration(ForbidHardcodedNamespacePrefixRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidHardcodedNamespacePrefixRector] Declarations over hardcoding — namespace prefix should be passed in as configuration.',
    ]);

    $rectorConfig->ruleWithConfiguration(RouteInfoRequiredRector::class, [
        'enumClass' => 'ZeroToProd\\Thryds\\Routes\\Route',
        'attributeClass' => 'ZeroToProd\\Thryds\\Attributes\\RouteInfo',
        'mode' => 'warn',
        'message' => "TODO: [RouteInfoRequiredRector] Attributes define properties — route case '%s' must declare #[RouteInfo] so the inventory graph can emit a description for this route.",
    ]);

    $rectorConfig->ruleWithConfiguration(RouteOperationRequiredRector::class, [
        'enumClass'      => 'ZeroToProd\\Thryds\\Routes\\Route',
        'attributeClass' => 'ZeroToProd\\Thryds\\Attributes\\RouteOperation',
        'mode'           => 'warn',
        'message'        => "TODO: [RouteOperationRequiredRector] Attributes define properties — route case '%s' must declare at least one #[RouteOperation] so the inventory graph can emit HTTP methods for this route.",
    ]);

    $rectorConfig->ruleWithConfiguration(RouteOperationRequiresRouteInfoRector::class, [
        'enumClass'              => 'ZeroToProd\\Thryds\\Routes\\Route',
        'triggerAttributeClass'  => 'ZeroToProd\\Thryds\\Attributes\\RouteOperation',
        'requiredAttributeClass' => 'ZeroToProd\\Thryds\\Attributes\\RouteInfo',
        'mode'                   => 'warn',
        'message'                => "TODO: [RouteOperationRequiresRouteInfoRector] Attributes define properties — route case '%s' declares #[RouteOperation] but is missing #[RouteInfo]. Both are required together: #[RouteOperation] declares HTTP methods, #[RouteInfo] declares the description.",
    ]);

    $rectorConfig->ruleWithConfiguration(RequirePersistsOnTableReferenceRector::class, [
        'tablesNamespace'      => 'ZeroToProd\\Thryds\\Tables',
        'attributeClass'       => 'ZeroToProd\\Thryds\\Attributes\\Persists',
        'controllersNamespace' => 'ZeroToProd\\Thryds\\Controllers',
        'mode'                 => 'warn',
        'message'              => "TODO: [RequirePersistsOnTableReferenceRector] Attributes define properties — '%s' imports '%s' from the tables namespace but is missing #[Persists(%s::class)]. Add it so the inventory graph shows the persistence edge.",
    ]);


    $rectorConfig->ruleWithConfiguration(UseColumnConstantsInQueriesRector::class, [
        'mode' => 'auto',
        'tableClasses' => [User::class, Migration::class],
    ]);


    // --- Exception Handler ---
    $rectorConfig->ruleWithConfiguration(RequireHandlesExceptionParamMatchRector::class, [
        'attributeClass' => \ZeroToProd\Thryds\Attributes\HandlesException::class,
        'mode' => 'auto',
        'message' => "TODO: [RequireHandlesExceptionParamMatchRector] Attributes define properties — #[HandlesException] declares %s but the method parameter type is %s. The attribute must match the parameter type.",
    ]);

    $rectorConfig->ruleWithConfiguration(RequireHandlesExceptionOnPublicHandlerMethodRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [RequireHandlesExceptionOnPublicHandlerMethodRector] Public method %s::%s accepts a Throwable subtype but is missing #[HandlesException] — it will never be dispatched. See: utils/rector/docs/RequireHandlesExceptionOnPublicHandlerMethodRector.md',
        'handlerAttributeClass' => 'ZeroToProd\\Thryds\\Attributes\\HandlesException',
        'throwableClass' => 'Throwable',
        'excludeMethods' => ['handle'],
    ]);

    $rectorConfig->ruleWithConfiguration(ForbidReflectionInInstanceMethodRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: Reflection on static class structure should be resolved at construction, not per-invocation. See: utils/rector/docs/ForbidReflectionInInstanceMethodRector.md',
    ]);

    $rectorConfig->ruleWithConfiguration(ForbidReflectionInClosureRector::class, [
        'mode' => 'warn',
        'message' => 'Reflection in closures runs per-invocation; hoist to the enclosing boot scope. See: utils/rector/docs/ForbidReflectionInClosureRector.md',
    ]);

    $rectorConfig->ruleWithConfiguration(RequireHandlesRouteAttributeRector::class, [
        'mode' => 'warn',
        'attributeClass' => \ZeroToProd\Thryds\Attributes\HandlesRoute::class,
        'controllerSuffixes' => ['Controller', 'Handler'],
        'controllersNamespace' => 'ZeroToProd\\Thryds\\Controllers',
        'message' => 'TODO: [RequireHandlesRouteAttributeRector] Attributes define properties — %s in Controllers/ is missing #[HandlesRoute]. Every controller must declare which route it handles so the router can discover it via reflection. See: utils/rector/docs/RequireHandlesRouteAttributeRector.md',
    ]);

    $rectorConfig->ruleWithConfiguration(EnforceLayerCoverageRector::class, [
        'layerEnum' => 'Layer',
        'segmentAttribute' => 'Segment',
        'srcDir' => 'src',
        'mode' => 'warn',
        'message' => 'TODO: [EnforceLayerCoverageRector] Namespace segment "%s" has no corresponding Layer enum case — add one to ensure attribute graph visibility. See: utils/rector/docs/EnforceLayerCoverageRector.md',
    ]);

    $rectorConfig->ruleWithConfiguration(ForbidInterfaceRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidInterfaceRector] Interfaces define implicit contracts — use PHP attributes to declare properties explicitly. Attributes are discoverable, enforceable, and composable without coupling. See: utils/rector/docs/ForbidInterfaceRector.md',
    ]);

    $rectorConfig->ruleWithConfiguration(ForbidClassInheritanceRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidClassInheritanceRector] Inheritance couples classes to parent implementation — use PHP attributes and composition instead. See: utils/rector/docs/ForbidClassInheritanceRector.md',
        'allowList' => [
            'ZeroToProd\Thryds\Tests\Integration\IntegrationTestCase',
            'ZeroToProd\Thryds\Tests\Database\DatabaseTestCase',
            'Zerotoprod\DataModel\Describe',
        ],
    ]);
};
