<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireParamTypeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireParamTypeRector::class, [
        'skipVariadic' => true,
        'useDocblocks' => true,
    ]);
};
