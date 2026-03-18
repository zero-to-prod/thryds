<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ValidateRequirementIdsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ValidateRequirementIdsRector::class, [
        'requirements_file' => __DIR__ . '/../requirements.yaml',
        'message' => "TODO: [ValidateRequirementIdsRector] Requirement ID '%s' not found in requirements.yaml. See: docs/requirement-tracing.md",
    ]);
};
