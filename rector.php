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

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/public',
        __DIR__ . '/tests',
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
        'message' => 'TODO: [ForbidCallableTypeVariableNameRector] rename $%s to describe its behaviour. See: utils/rector/docs/ForbidCallableTypeVariableNameRector.md',
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
        'message' => 'TODO: [opcache] add a type declaration to improve OPcache optimization. See: utils/rector/docs/RequireTypedPropertyRector.md',
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
        'maxParams' => 5,
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
    $rectorConfig->ruleWithConfiguration(SuggestExtractSharedCatchLogicRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestExtractSharedCatchLogicRector] Multiple catch blocks instantiate the same classes (%s). Consider extracting the shared logic. See: utils/rector/docs/SuggestExtractSharedCatchLogicRector.md',
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestDuplicateStringConstantRector::class, [
        'mode' => 'warn',
        'message' => "TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string '%s' (used %dx) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md",
        'ignoredAttributeClasses' => [
            Requirement::class,
        ],
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestConstArrayToEnumRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: Consider migrating const arrays to a backed enum with #[Group] attributes. See: utils/rector/docs/SuggestConstArrayToEnumRector.md',
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
        'mode' => 'warn',
        'message' => 'TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. $%s has values: %s. Extract to a backed enum. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md',
        'callSiteMessage' => 'TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. %s is a value of %s::$%s. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md',
    ]);

    // --- Magic String Elimination ---
    $rectorConfig->rule(StringClassNameToClassConstantRector::class);
    $rectorConfig->ruleWithConfiguration(ForbidMagicStringArrayKeyRector::class, [
        'excludedClasses' => [Log::class],
        'mode' => 'warn',
        'message' => "TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value '%s' on the appropriate class. See: utils/rector/docs/ForbidMagicStringArrayKeyRector.md",
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
        'message' => 'TODO: [ForbidMagicHttpStatusCodeRector] Replace %d with a named HTTP status constant or enum case. See: utils/rector/docs/ForbidMagicHttpStatusCodeRector.md',
    ]);

    // --- OPcache Optimization ---
    $rectorConfig->ruleWithConfiguration(ForbidDynamicIncludeRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [opcache] dynamic include prevents OPcache optimization. See: utils/rector/docs/ForbidDynamicIncludeRector.md',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidVariableVariablesRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [opcache] variable variables prevent compile-time variable resolution. See: utils/rector/docs/ForbidVariableVariablesRector.md',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidErrorSuppressionRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [opcache] @ error suppression adds per-call overhead — handle errors explicitly. See: utils/rector/docs/ForbidErrorSuppressionRector.md',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidGlobalKeywordRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [opcache] global keyword prevents scope-level optimization. See: utils/rector/docs/ForbidGlobalKeywordRector.md',
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
        'message' => 'TODO: [RequireLogEventRector] Log calls need a durable event id. Add `%s::%s => %s::<event_label>` to the context array. See: utils/rector/docs/RequireLogEventRector.md',
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

    // --- DataModel & ViewModel ---
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
        'message' => 'TODO: [SuggestAttributeForRepeatedPropertyPatternRector] %s uses %s + %s — add #[%s] attribute. See: utils/rector/docs/SuggestAttributeForRepeatedPropertyPatternRector.md',
    ]);
    $rectorConfig->ruleWithConfiguration(UseClassConstArrayKeyForDataModelRector::class, [
        'mode' => 'auto',
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

    // --- Route Safety ---
    $rectorConfig->ruleWithConfiguration(ForbidDirectRouterInstantiationRector::class, [
        'forbiddenClasses' => [Router::class],
        'mode' => 'warn',
        'message' => 'TODO: [ForbidDirectRouterInstantiationRector] Use League\\Route\\Cache\\Router instead of instantiating %s directly. Direct instantiation bypasses route caching. See: utils/rector/docs/ForbidDirectRouterInstantiationRector.md',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidStringRoutePatternRector::class, [
        'methods' => ['map'],
        'argPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [ForbidStringRoutePatternRector] Replace inline string '%s' with a Route enum case reference (e.g. Route::case->value). See: utils/rector/docs/ForbidStringRoutePatternRector.md",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireRouteEnumInMapCallRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'methods' => ['map'],
        'argPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '%s' instead. See: utils/rector/docs/RequireRouteEnumInMapCallRector.md",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireViewEnumInMakeCallRector::class, [
        'enumClass' => View::class,
        'methodName' => 'make',
        'paramName' => 'view',
        'mode' => 'auto',
        'message' => "TODO: [RequireViewEnumInMakeCallRector] Use View::%s->value instead of string '%s'. See: utils/rector/docs/RequireViewEnumInMakeCallRector.md",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidHardcodedRouteStringRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'mode' => 'warn',
        'message' => "TODO: [ForbidHardcodedRouteStringRector] Use Route::%s->value instead of hardcoded '%s'. See: utils/rector/docs/ForbidHardcodedRouteStringRector.md",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDuplicateRouteRegistrationRector::class, [
        'methods' => ['map'],
        'methodArgPosition' => 0,
        'routeArgPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [ForbidDuplicateRouteRegistrationRector] Duplicate route registration: '%s %s' was already registered above. See: utils/rector/docs/ForbidDuplicateRouteRegistrationRector.md",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireAllRouteCasesRegisteredRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'methods' => ['map'],
        'argPosition' => 1,
        'scanDir' => __DIR__ . '/src',
        'mode' => 'warn',
        'message' => "TODO: [RequireAllRouteCasesRegisteredRector] Route case '%s' is defined but never registered in any router map() call. See: utils/rector/docs/RequireAllRouteCasesRegisteredRector.md",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireRouteTestRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'testDir' => __DIR__ . '/tests',
        'mode' => 'warn',
        'message' => "TODO: [RequireRouteTestRector] Route case '%s' has no corresponding test. Add a test that exercises this route. See: utils/rector/docs/RequireRouteTestRector.md",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireRoutePatternConstRector::class, [
        'classSuffix' => 'Route',
        'constName' => 'pattern',
        'mode' => 'warn',
        'message' => "TODO: [RequireRoutePatternConstRector] Route class '%s' is missing a '%s' constant — define: public const string %s = '/...'. See: utils/rector/docs/RequireRoutePatternConstRector.md",
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
        'message' => "TODO: [ForbidDuplicateRoutePatternRector] Duplicate route pattern '%s' — already defined in %s::%s. See: utils/rector/docs/ForbidDuplicateRoutePatternRector.md",
    ]);
    $rectorConfig->ruleWithConfiguration(ExtractRoutePatternToRouteClassRector::class, [
        'methods' => ['map'],
        'argPosition' => 1,
        'namespace' => 'ZeroToProd\\Thryds\\Routes',
        'outputDir' => __DIR__ . '/src/Routes',
        'mode' => 'warn',
        'message' => 'TODO: [ExtractRoutePatternToRouteClassRector] Extract inline route string to a Route class constant. See: utils/rector/docs/ExtractRoutePatternToRouteClassRector.md',
    ]);

    // --- Environment Key Safety ---
    $rectorConfig->ruleWithConfiguration(ForbidEnvCheckOutsideConfigRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidBareServerEnvKeyRector::class, [
        'envClass' => Env::class,
        'superglobals' => ['_SERVER', '_ENV'],
        'mode' => 'auto',
        'message' => "TODO: [ForbidBareServerEnvKeyRector] Use %s::%s instead of bare string '%s'. See: utils/rector/docs/ForbidBareServerEnvKeyRector.md",
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
        'message' => 'TODO: [RequireEnumValueAccessRector] %s::%s is a backed enum case — use ->value to get the string. See: utils/rector/docs/RequireEnumValueAccessRector.md',
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
        'message' => "TODO: [ForbidStringComparisonOnEnumPropertyRector] Compare against %s::%s instead of string '%s'. See: utils/rector/docs/ForbidStringComparisonOnEnumPropertyRector.md",
    ]);

    // --- Constants Class Design ---
    $rectorConfig->ruleWithConfiguration(RequireNamesKeysOnConstantsClassRector::class, [
        'attributeClass' => KeyRegistry::class,
        'excludedAttributes' => [
            ViewModel::class,
        ],
        'mode' => 'warn',
        'message' => 'TODO: [RequireNamesKeysOnConstantsClassRector] %s contains only string constants — add #[NamesKeys] to declare what they name (ADR-007). See: utils/rector/docs/RequireNamesKeysOnConstantsClassRector.md',
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
        'message' => 'TODO: [RequireNamesKeysOnMixedConstantsClassRector] %s has %d string constants — add #[NamesKeys] to declare what they name (ADR-007). See: utils/rector/docs/RequireNamesKeysOnMixedConstantsClassRector.md',
    ]);

    // --- Enum Design ---
    $rectorConfig->ruleWithConfiguration(RequireClosedSetOnBackedEnumRector::class, [
        'attributeClass' => ClosedSet::class,
        'mode' => 'warn',
        'message' => 'TODO: [RequireClosedSetOnBackedEnumRector] Backed enum %s must declare #[ClosedSet] — enums limit choices (ADR-007). See: utils/rector/docs/RequireClosedSetOnBackedEnumRector.md',
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestEnumForNameEqualsValueConstRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestEnumForNameEqualsValueConstRector] %s has %d string constants where name equals value — consider migrating to a backed enum. See: utils/rector/docs/SuggestEnumForNameEqualsValueConstRector.md',
        'minConstants' => 2,
        'excludedAttributes' => [
            ClosedSet::class,
            KeyRegistry::class,
        ],
    ]);
    $rectorConfig->ruleWithConfiguration(RequireExhaustiveMatchOnEnumRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [RequireExhaustiveMatchOnEnumRector] match() on %s must cover all cases explicitly. See: utils/rector/docs/RequireExhaustiveMatchOnEnumRector.md',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireEnumForBranchingConstantRector::class, [
        'mode' => 'warn',
        'minCases' => 3,
        'message' => 'TODO: [RequireEnumForBranchingConstantRector] $%s is compared against %d literals (%s) — this is an implicit closed set. Consider extracting to a backed enum and using match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md',
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
        'message' => "TODO: [ForbidStringArgForEnumParamRector] '%s' matches %s::%s — use %s::%s->value. See: utils/rector/docs/ForbidStringArgForEnumParamRector.md",
    ]);

    $rectorConfig->ruleWithConfiguration(RequireConstForRepeatedArrayKeyRector::class, [
        'minOccurrences' => 2,
        'minLength' => 3,
        'excludedKeys' => ['class', 'mode', 'message'],
        'excludedClasses' => [LogContext::class, OpcacheStatus::class],
        'mode' => 'warn',
        'message' => "TODO: [RequireConstForRepeatedArrayKeyRector] '%s' used %dx as array key — extract to a class constant. See: utils/rector/docs/RequireConstForRepeatedArrayKeyRector.md",
    ]);


    // --- Blade / htmx ---
    $rectorConfig->ruleWithConfiguration(RequireFragmentIfForBladeRenderRector::class, [
        'mode' => 'warn',
        'message' => "TODO: [RequireFragmentIfForBladeRenderRector] ->render() returns the full page. For htmx partial requests, use ->fragmentIf(\$request->hasHeader(Header::hx_request), 'body') instead. See: utils/rector/docs/RequireFragmentIfForBladeRenderRector.md",
    ]);

    // --- Checklist Validation ---
    $rectorConfig->ruleWithConfiguration(ValidateChecklistPathsRector::class, [
        'attributes' => [
            ['attributeClass' => SourceOfTruth::class, 'paramName' => 'addCase'],
            ['attributeClass' => ClosedSet::class, 'paramName' => 'addCase'],
            ['attributeClass' => KeyRegistry::class, 'paramName' => 'addKey'],
        ],
        'projectDir' => __DIR__,
        'mode' => 'warn',
        'message' => "TODO: [ValidateChecklistPathsRector] %s references '%s' in %s, but this file does not exist. Update the checklist. See: utils/rector/docs/ValidateChecklistPathsRector.md",
    ]);

    $rectorConfig->ruleWithConfiguration(SuggestEnumForKeyRegistryWithMethodsRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestEnumForKeyRegistryWithMethodsRector] %s has #[KeyRegistry] but also contains methods — extract constants to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForKeyRegistryWithMethodsRector.md',
    ]);

    $rectorConfig->ruleWithConfiguration(SuggestEnumForInternalOnlyConstantsRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestEnumForInternalOnlyConstantsRector] %s has %d string constants only referenced internally — consider migrating to a backed enum. See: utils/rector/docs/SuggestEnumForInternalOnlyConstantsRector.md',
    ]);

    $rectorConfig->ruleWithConfiguration(ForbidCrossFileStringDuplicationRector::class, [
        'mode' => 'warn',
        'minFiles' => 3,
        'message' => "TODO: [ForbidCrossFileStringDuplicationRector] string '%s' appears in %d files. Extract to a shared constant. See: utils/rector/docs/ForbidCrossFileStringDuplicationRector.md",
    ]);

    $rectorConfig->ruleWithConfiguration(DetectParallelBladePhpBehaviorRector::class, [
        'mode' => 'warn',
        'message' => "TODO: [DetectParallelBladePhpBehaviorRector] Use %s::%s instead of hardcoded '%s'. See: utils/rector/docs/DetectParallelBladePhpBehaviorRector.md",
    ]);

    // --- Requirement Tracing ---
    $rectorConfig->ruleWithConfiguration(ValidateRequirementIdsRector::class, [
        'requirements_file' => __DIR__ . '/requirements.yaml',
        'message' => "TODO: [ValidateRequirementIdsRector] Requirement ID '%s' not found in requirements.yaml. See: docs/requirement-tracing.md",
    ]);
};
