<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidCrossFileStringDuplicationRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidCrossFileStringDuplicationRector::class, [
        'mode' => 'warn',
        'minFiles' => 3,
        // Pre-seed the cross-file accumulator so the single-file test fixture sees
        // 'button-primary' as already appearing in 2 other files. When the fixture
        // file is processed it becomes file #3, crossing the threshold.
        'preSeededFilesByValue' => [
            'button-primary' => [
                '/app/resources/views/components/button.blade.php',
                '/app/src/UI/ButtonVariant.php',
            ],
        ],
    ]);
};
