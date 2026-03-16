<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ExtractRepeatedExpressionToVariableRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ExtractRepeatedExpressionToVariableRector::class, [
        'dirname',
    ]);
};
