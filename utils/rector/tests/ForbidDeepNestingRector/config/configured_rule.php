<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidDeepNestingRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidDeepNestingRector::class, [
        'maxDepth' => 2,
        'maxNegationComplexity' => 2,
    ]);
};
