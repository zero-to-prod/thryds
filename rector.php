<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Utils\Rector\Rector\AddNamedArgWhenVarMismatchesParamRector;
use Utils\Rector\Rector\ExtractRepeatedExpressionToVariableRector;
use Utils\Rector\Rector\ExtractRoutePatternToRouteClassRector;
use Utils\Rector\Rector\ForbidLongClosureRector;
use Utils\Rector\Rector\ForbiddenFuncCallRector;
use Utils\Rector\Rector\ForbidCallableTypeVariableNameRector;
use Utils\Rector\Rector\ForbidDuplicateRoutePatternRector;
use Utils\Rector\Rector\ForbidExitInSourceRector;
use Utils\Rector\Rector\ForbidDynamicIncludeRector;
use Utils\Rector\Rector\ForbidGlobalKeywordRector;
use Utils\Rector\Rector\ForbidErrorSuppressionRector;
use Utils\Rector\Rector\ForbidDirectRouterInstantiationRector;
use Utils\Rector\Rector\ForbidEvalRector;
use Utils\Rector\Rector\ForbidMagicStringArrayKeyRector;
use Utils\Rector\Rector\ForbidVariableVariablesRector;
use Utils\Rector\Rector\ForbidStringRoutePatternRector;
use Utils\Rector\Rector\FrankenPhpLogToLogClassRector;
use Utils\Rector\Rector\MakeClassReadonlyRector;
use Utils\Rector\Rector\MigrateArrayToDataModelRector;
use Utils\Rector\Rector\RemoveNamedArgWhenVarMatchesParamRector;
use Utils\Rector\Rector\RenameParamToMatchTypeNameRector;
use Utils\Rector\Rector\RenameVarToMatchReturnTypeRector;
use Utils\Rector\Rector\ReplaceFullyQualifiedNameRector;
use Utils\Rector\Rector\RequireTypedPropertyRector;
use Utils\Rector\Rector\RequireRoutePatternConstRector;
use Utils\Rector\Rector\RouteParamNameMustBeConstRector;
use Utils\Rector\Rector\StringArgToClassConstRector;
use Utils\Rector\Rector\SuggestEnumForStringPropertyRector;
use Utils\Rector\Rector\UseClassConstArrayKeyForDataModelRector;
use Utils\Rector\Rector\RequireLogEventRector;
use Utils\Rector\Rector\RenameEnumCaseToMatchValueRector;
use Utils\Rector\Rector\RenamePrimitivePropertyToSnakeCaseRector;
use Utils\Rector\Rector\RenamePrimitiveVarToSnakeCaseRector;
use Utils\Rector\Rector\RenamePropertyToMatchTypeNameRector;
use Utils\Rector\Rector\RequireMethodAnnotationForDataModelRector;
use Utils\Rector\Rector\SuggestDuplicateStringConstantRector;
use Utils\Rector\Rector\SuggestExtractSharedCatchLogicRector;
use Utils\Rector\Rector\RequireParamTypeRector;
use Utils\Rector\Rector\RequireReturnTypeRector;
use Utils\Rector\Rector\ForbidArrayShapeReturnRector;
use Utils\Rector\Rector\ForbidDeepNestingRector;
use Utils\Rector\Rector\LimitConstructorParamsRector;
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
        'error_log',
        'extract',
        'compact',
        'session_start',
    ]);
    $rectorConfig->ruleWithConfiguration(FrankenPhpLogToLogClassRector::class, [
        'functions' => ['frankenphp_log'],
        'logClass' => Log::class,
    ]);
    $rectorConfig->rule(RenameParamToMatchTypeNameRector::class);
    $rectorConfig->ruleWithConfiguration(RenameVarToMatchReturnTypeRector::class, [
        'skipNames' => ['Closure'],
    ]);
    $rectorConfig->rule(AddNamedArgWhenVarMismatchesParamRector::class);
    $rectorConfig->rule(RemoveNamedArgWhenVarMatchesParamRector::class);
    $rectorConfig->rule(RemoveUnusedPrivateMethodParameterRector::class);
    $rectorConfig->rule(RemoveUnusedPublicMethodParameterRector::class);
    $rectorConfig->rule(UseClassConstArrayKeyForDataModelRector::class);
    $rectorConfig->rule(StringClassNameToClassConstantRector::class);
    $rectorConfig->ruleWithConfiguration(RequireLogEventRector::class, [
        'logClass' => Log::class,
        'eventKey' => 'event',
        'message' => 'TODO: [RequireLogEventRector] Log calls need a durable event id. Add `%s::%s => %s::<event_label>` to the context array.',
    ]);
    $rectorConfig->ruleWithConfiguration(UseLogContextConstRector::class, [
        'logClass' => Log::class,
        'keys' => ['exception', 'file', 'line'],
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidMagicStringArrayKeyRector::class, [
        'excludedClasses' => [Log::class],
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
        'message' => 'TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. $%s has values: %s. Extract to a backed enum.',
        'callSiteMessage' => 'TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. %s is a value of %s::$%s. Replace with enum case.',
    ]);
    $rectorConfig->ruleWithConfiguration(ExtractRepeatedExpressionToVariableRector::class, [
        'dirname',
    ]);
    $rectorConfig->ruleWithConfiguration(StringArgToClassConstRector::class, [
        [
            'class' => View::class,
            'methodName' => 'make',
            'paramName' => 'view',
        ],
    ]);
    $rectorConfig->rule(MakeClassReadonlyRector::class);
    $rectorConfig->rule(RenameEnumCaseToMatchValueRector::class);
    $rectorConfig->rule(RenamePrimitivePropertyToSnakeCaseRector::class);
    $rectorConfig->rule(RenamePrimitiveVarToSnakeCaseRector::class);
    $rectorConfig->rule(RenamePropertyToMatchTypeNameRector::class);
    $rectorConfig->ruleWithConfiguration(MigrateArrayToDataModelRector::class, [
        [
            'methodName' => 'make',
            'dataParam' => 'data',
            'viewParam' => 'view',
            'viewModelNamespace' => 'ZeroToProd\\Thryds\\ViewModels',
            'viewModelDir' => __DIR__ . '/src/ViewModels',
            'templateDir' => __DIR__ . '/templates',
            'dataModelTrait' => 'Zerotoprod\\DataModel\\DataModel',
        ],
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidStringRoutePatternRector::class, [
        'methods' => ['map'],
        'argPosition' => 1,
        'message' => "TODO: [ForbidStringRoutePatternRector] Route patterns must be class constant references, not inline strings. Extract '%s' to a Route class constant.",
    ]);
    $rectorConfig->ruleWithConfiguration(ExtractRoutePatternToRouteClassRector::class, [
        'methods' => ['map'],
        'argPosition' => 1,
        'namespace' => 'ZeroToProd\\Thryds\\Routes',
        'outputDir' => __DIR__ . '/src/Routes',
    ]);
    $rectorConfig->ruleWithConfiguration(RouteParamNameMustBeConstRector::class, [
        'classSuffix' => 'Route',
        'constName' => 'pattern',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireRoutePatternConstRector::class, [
        'classSuffix' => 'Route',
        'constName' => 'pattern',
        'excludedClasses' => ['WebRoutes'],
        'message' => "TODO: [RequireRoutePatternConstRector] Route class '%s' is missing a '%s' constant. Define: public const string %s = '/...';",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDuplicateRoutePatternRector::class, [
        'classSuffix' => 'Route',
        'constNames' => ['pattern'],
        'scanDir' => __DIR__ . '/src/Routes',
        'message' => "TODO: [ForbidDuplicateRoutePatternRector] Duplicate route pattern '%s'. This pattern is already defined in %s::%s. Remove or rename this constant.",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDirectRouterInstantiationRector::class, [
        'forbiddenClasses' => ['League\\Route\\Router'],
        'message' => 'TODO: [ForbidDirectRouterInstantiationRector] Use League\\Route\\Cache\\Router instead of instantiating %s directly. Direct instantiation bypasses route caching.',
    ]);
    $rectorConfig->rule(ForbidEvalRector::class);
    $rectorConfig->rule(ForbidExitInSourceRector::class);
    $rectorConfig->ruleWithConfiguration(ForbidDynamicIncludeRector::class, [
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
        'message' => 'TODO: [ForbidCallableTypeVariableNameRector] rename $%s to describe its behaviour',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireTypedPropertyRector::class, [
        'message' => 'TODO: [opcache] add a type declaration to improve OPcache optimization',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidVariableVariablesRector::class, [
        'message' => 'TODO: [opcache] variable variables prevent compile-time variable resolution',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidErrorSuppressionRector::class, [
        'message' => 'TODO: [opcache] @ error suppression adds per-call overhead — handle errors explicitly',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidGlobalKeywordRector::class, [
        'message' => 'TODO: [opcache] global keyword prevents scope-level optimization',
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestExtractSharedCatchLogicRector::class, [
        'message' => 'TODO: [SuggestExtractSharedCatchLogicRector] Multiple catch blocks instantiate the same classes (%s). Consider extracting the shared logic.',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireReturnTypeRector::class, [
        'skipMagicMethods' => true,
        'skipClosures' => false,
    ]);
    $rectorConfig->ruleWithConfiguration(RequireParamTypeRector::class, [
        'skipVariadic' => true,
        'useDocblocks' => true,
    ]);
    $rectorConfig->ruleWithConfiguration(SuggestDuplicateStringConstantRector::class, [
        'message' => "TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string '%s' (used %dx) to a single source of truth. Consts name things, enums limit choices, attributes define properties.",
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidLongClosureRector::class, [
        'maxStatements' => 5,
        'skipArrowFunctions' => true,
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDeepNestingRector::class, [
        'maxDepth' => 3,
        'maxNegationComplexity' => 2,
    ]);
    $rectorConfig->ruleWithConfiguration(LimitConstructorParamsRector::class, [
        'maxParams' => 5,
        'dtoSuffix' => 'Deps',
    ]);
    $rectorConfig->ruleWithConfiguration(RequireMethodAnnotationForDataModelRector::class, [
        'dataModelTraits' => [
            DataModel::class,
            \ZeroToProd\Thryds\Helpers\DataModel::class,
        ],
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidArrayShapeReturnRector::class, [
        'minKeys' => 2,
        'classSuffix' => 'Result',
    ]);
    $rectorConfig->ruleWithConfiguration(ReplaceFullyQualifiedNameRector::class, [
        DataModel::class => \ZeroToProd\Thryds\Helpers\DataModel::class,
        Describe::class => \ZeroToProd\Thryds\Helpers\Describe::class,
    ]);
};
