<?php

declare(strict_types=1);

use League\Route\Router;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Utils\Rector\Rector\AddNamedArgWhenVarMismatchesParamRector;
use Utils\Rector\Rector\ExtractRepeatedExpressionToVariableRector;
use Utils\Rector\Rector\ForbidArrayShapeReturnRector;
use Utils\Rector\Rector\ForbidCallableTypeVariableNameRector;
use Utils\Rector\Rector\ForbidDeepNestingRector;
use Utils\Rector\Rector\ForbiddenFuncCallRector;
use Utils\Rector\Rector\ForbidDirectRouterInstantiationRector;
use Utils\Rector\Rector\ForbidStringRoutePatternRector;
use Utils\Rector\Rector\ForbidDynamicIncludeRector;
use Utils\Rector\Rector\ForbidErrorSuppressionRector;
use Utils\Rector\Rector\ForbidEvalRector;
use Utils\Rector\Rector\ForbidExitInSourceRector;
use Utils\Rector\Rector\ForbidGlobalKeywordRector;
use Utils\Rector\Rector\ForbidLongClosureRector;
use Utils\Rector\Rector\ForbidMagicStringArrayKeyRector;
use Utils\Rector\Rector\ForbidVariableVariablesRector;
use Utils\Rector\Rector\FrankenPhpLogToLogClassRector;
use Utils\Rector\Rector\LimitConstructorParamsRector;
use Utils\Rector\Rector\MakeClassReadonlyRector;
use Utils\Rector\Rector\MigrateArrayToDataModelRector;
use Utils\Rector\Rector\RemoveNamedArgWhenVarMatchesParamRector;
use Utils\Rector\Rector\RenameEnumCaseToMatchValueRector;
use Utils\Rector\Rector\RenameParamToMatchTypeNameRector;
use Utils\Rector\Rector\RenamePrimitivePropertyToSnakeCaseRector;
use Utils\Rector\Rector\RenamePrimitiveVarToSnakeCaseRector;
use Utils\Rector\Rector\RenamePropertyToMatchTypeNameRector;
use Utils\Rector\Rector\RenameVarToMatchReturnTypeRector;
use Utils\Rector\Rector\ReplaceFullyQualifiedNameRector;
use Utils\Rector\Rector\RequireLogEventRector;
use Utils\Rector\Rector\RequireMethodAnnotationForDataModelRector;
use Utils\Rector\Rector\ForbidDuplicateRouteRegistrationRector;
use Utils\Rector\Rector\RequireAllRouteCasesRegisteredRector;
use Utils\Rector\Rector\RequireNamedArgForBoolParamRector;
use Utils\Rector\Rector\ForbidHardcodedRouteStringRector;
use Utils\Rector\Rector\RequireRouteEnumInMapCallRector;
use Utils\Rector\Rector\RequireParamTypeRector;
use Utils\Rector\Rector\RequireReturnTypeRector;
use Utils\Rector\Rector\RequireTypedPropertyRector;
use Utils\Rector\Rector\StringArgToClassConstRector;
use Utils\Rector\Rector\SuggestDuplicateStringConstantRector;
use Utils\Rector\Rector\SuggestEnumForStringPropertyRector;
use Utils\Rector\Rector\SuggestExtractSharedCatchLogicRector;
use Utils\Rector\Rector\UseClassConstArrayKeyForDataModelRector;
use Utils\Rector\Rector\UseLogContextConstRector;
use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;
use ZeroToProd\Thryds\Helpers\View;
use ZeroToProd\Thryds\Log;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/public',
        __DIR__ . '/tests',
    ]);
    $rectorConfig->importNames();
    $rectorConfig->ruleWithConfiguration(ForbiddenFuncCallRector::class, [
        'functions' => [
            'error_log',
            'extract',
            'compact',
            'session_start',
        ],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(FrankenPhpLogToLogClassRector::class, [
        'functions' => ['frankenphp_log'],
        'logClass' => Log::class,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RenameParamToMatchTypeNameRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RenameVarToMatchReturnTypeRector::class, [
        'skipNames' => ['Closure'],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(AddNamedArgWhenVarMismatchesParamRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RemoveNamedArgWhenVarMatchesParamRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->rule(RemoveUnusedPrivateMethodParameterRector::class);
    $rectorConfig->rule(RemoveUnusedPublicMethodParameterRector::class);
    $rectorConfig->ruleWithConfiguration(UseClassConstArrayKeyForDataModelRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->rule(StringClassNameToClassConstantRector::class);
    $rectorConfig->ruleWithConfiguration(RequireLogEventRector::class, [
        'logClass' => Log::class,
        'eventKey' => 'event',
        'mode' => 'warn',
        'message' => 'TODO: [RequireLogEventRector] Log calls need a durable event id. Add `%s::%s => %s::<event_label>` to the context array.',
    ]);
    $rectorConfig->ruleWithConfiguration(UseLogContextConstRector::class, [
        'logClass' => Log::class,
        'keys' => ['exception', 'file', 'line'],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidMagicStringArrayKeyRector::class, [
        'excludedClasses' => [Log::class],
        'mode' => 'warn',
        'message' => "TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value '%s' on the appropriate class.",
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestEnumForStringPropertyRector::class, [
        'dataModelTraits' => [
            DataModel::class,
            \ZeroToProd\Thryds\Helpers\DataModel::class,
        ],
        'describeAttrs' => [
            Describe::class,
            \ZeroToProd\Thryds\Helpers\Describe::class,
        ],
        'mode' => 'warn',
        'message' => 'TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. $%s has values: %s. Extract to a backed enum.',
        'callSiteMessage' => 'TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. %s is a value of %s::$%s. Replace with enum case.',
    ]);
    $rectorConfig->ruleWithConfiguration(ExtractRepeatedExpressionToVariableRector::class, [
        'functions' => ['dirname'],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(StringArgToClassConstRector::class, [
        'mappings' => [
            [
                'class' => View::class,
                'methodName' => 'make',
                'paramName' => 'view',
            ],
        ],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(MakeClassReadonlyRector::class, [
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
    $rectorConfig->ruleWithConfiguration(MigrateArrayToDataModelRector::class, [
        'mappings' => [
            [
                'methodName' => 'make',
                'dataParam' => 'data',
                'viewParam' => 'view',
                'viewModelNamespace' => 'ZeroToProd\\Thryds\\ViewModels',
                'viewModelDir' => __DIR__ . '/src/ViewModels',
                'templateDir' => __DIR__ . '/templates',
                'dataModelTrait' => 'Zerotoprod\\DataModel\\DataModel',
            ],
        ],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDirectRouterInstantiationRector::class, [
        'forbiddenClasses' => [Router::class],
        'mode' => 'warn',
        'message' => 'TODO: [ForbidDirectRouterInstantiationRector] Use League\\Route\\Cache\\Router instead of instantiating %s directly. Direct instantiation bypasses route caching.',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidStringRoutePatternRector::class, [
        'methods' => ['map'],
        'argPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [ForbidStringRoutePatternRector] Replace inline string '%s' with a Route enum case reference (e.g. Route::case->value).",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidEvalRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidExitInSourceRector::class, [
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDynamicIncludeRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [opcache] dynamic include prevents OPcache optimization',
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
        'message' => 'TODO: [ForbidCallableTypeVariableNameRector] rename $%s to describe its behaviour',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireTypedPropertyRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [opcache] add a type declaration to improve OPcache optimization',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidVariableVariablesRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [opcache] variable variables prevent compile-time variable resolution',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidErrorSuppressionRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [opcache] @ error suppression adds per-call overhead — handle errors explicitly',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidGlobalKeywordRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [opcache] global keyword prevents scope-level optimization',
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestExtractSharedCatchLogicRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestExtractSharedCatchLogicRector] Multiple catch blocks instantiate the same classes (%s). Consider extracting the shared logic.',
    ]);
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
    $rectorConfig->ruleWithConfiguration(SuggestDuplicateStringConstantRector::class, [
        'mode' => 'warn',
        'message' => "TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string '%s' (used %dx) to a single source of truth. Consts name things, enums limit choices, attributes define properties.",
    ]);
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
    $rectorConfig->ruleWithConfiguration(RequireMethodAnnotationForDataModelRector::class, [
        'dataModelTraits' => [
            DataModel::class,
            \ZeroToProd\Thryds\Helpers\DataModel::class,
        ],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidArrayShapeReturnRector::class, [
        'minKeys' => 2,
        'classSuffix' => 'Result',
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(ReplaceFullyQualifiedNameRector::class, [
        'replacements' => [
            DataModel::class => \ZeroToProd\Thryds\Helpers\DataModel::class,
            Describe::class => \ZeroToProd\Thryds\Helpers\Describe::class,
        ],
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireNamedArgForBoolParamRector::class, [
        'skipBuiltinFunctions' => false,
        'skipWhenOnlyArg' => true,
        'mode' => 'auto',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireRouteEnumInMapCallRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'methods' => ['map'],
        'argPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '%s' instead.",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidHardcodedRouteStringRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'mode' => 'warn',
        'message' => "TODO: [ForbidHardcodedRouteStringRector] Use Route::%s->value instead of hardcoded '%s'.",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDuplicateRouteRegistrationRector::class, [
        'methods' => ['map'],
        'methodArgPosition' => 0,
        'routeArgPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [ForbidDuplicateRouteRegistrationRector] Duplicate route registration: '%s %s' was already registered above.",
    ]);
    $rectorConfig->ruleWithConfiguration(RequireAllRouteCasesRegisteredRector::class, [
        'enumClass' => \ZeroToProd\Thryds\Routes\Route::class,
        'methods' => ['map'],
        'argPosition' => 1,
        'scanDir' => __DIR__ . '/src',
        'mode' => 'warn',
        'message' => "TODO: [RequireAllRouteCasesRegisteredRector] Route case '%s' is defined but never registered in any router map() call.",
    ]);
};
