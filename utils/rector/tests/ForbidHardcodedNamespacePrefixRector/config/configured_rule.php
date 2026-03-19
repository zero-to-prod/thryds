<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidHardcodedNamespacePrefixRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidHardcodedNamespacePrefixRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidHardcodedNamespacePrefixRector] Hardcoded namespace prefix should be passed in as configuration',
    ]);
};
