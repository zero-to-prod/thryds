<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\DetectParallelBladePhpBehaviorRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(DetectParallelBladePhpBehaviorRector::class, [
        'mode' => 'warn',
        // Pre-seed the registry with constants/enum values collected from "other files".
        // When the fixture file is processed, these values are already known so the rule
        // can flag matching string literals.
        'preSeededValues' => [
            'primary' => ['class' => 'App\\UI\\ButtonVariant', 'const' => 'Primary'],
            'btn-large' => ['class' => 'App\\UI\\ButtonSize', 'const' => 'Large'],
            'alert-danger' => ['class' => 'App\\UI\\AlertVariant', 'const' => 'Danger'],
        ],
    ]);
};
