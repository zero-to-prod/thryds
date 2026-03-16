<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Utils\Rector\Rector\AddNamedArgWhenVarMismatchesParamRector;
use Utils\Rector\Rector\ExtractRepeatedExpressionToVariableRector;
use Utils\Rector\Rector\ExtractRoutePatternToRouteClassRector;
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
use Utils\Rector\Rector\SuggestExtractSharedCatchLogicRector;
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
    ]);
    $rectorConfig->ruleWithConfiguration(UseLogContextConstRector::class, [
        'logClass' => Log::class,
        'keys' => ['exception', 'file', 'line'],
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidMagicStringArrayKeyRector::class, [
        Log::class,
    ]);
    $rectorConfig->rule(SuggestEnumForStringPropertyRector::class);
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
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDuplicateRoutePatternRector::class, [
        'classSuffix' => 'Route',
        'constNames' => ['pattern'],
        'scanDir' => __DIR__ . '/src/Routes',
    ]);
    $rectorConfig->ruleWithConfiguration(ForbidDirectRouterInstantiationRector::class, [
        'League\\Route\\Router',
    ]);
    $rectorConfig->rule(ForbidEvalRector::class);
    $rectorConfig->rule(ForbidExitInSourceRector::class);
    $rectorConfig->rule(ForbidDynamicIncludeRector::class);
    $rectorConfig->ruleWithConfiguration(ForbidCallableTypeVariableNameRector::class, [
        'Closure',
        'Callable',
        'Callback',
        'Function',
        'Func',
    ]);
    $rectorConfig->rule(RequireTypedPropertyRector::class);
    $rectorConfig->rule(ForbidVariableVariablesRector::class);
    $rectorConfig->rule(ForbidErrorSuppressionRector::class);
    $rectorConfig->rule(ForbidGlobalKeywordRector::class);
    $rectorConfig->rule(SuggestExtractSharedCatchLogicRector::class);
    $rectorConfig->rule(RequireMethodAnnotationForDataModelRector::class);
    $rectorConfig->ruleWithConfiguration(ReplaceFullyQualifiedNameRector::class, [
        DataModel::class => \ZeroToProd\Thryds\Helpers\DataModel::class,
        Describe::class => \ZeroToProd\Thryds\Helpers\Describe::class,
    ]);
};
