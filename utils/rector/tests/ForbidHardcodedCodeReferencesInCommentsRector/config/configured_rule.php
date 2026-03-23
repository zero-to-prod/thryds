<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidHardcodedCodeReferencesInCommentsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidHardcodedCodeReferencesInCommentsRector::class, [
        'mode' => 'warn',
    ]);
};
