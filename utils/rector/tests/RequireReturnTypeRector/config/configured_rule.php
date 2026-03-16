<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireReturnTypeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireReturnTypeRector::class, [
        'skipMagicMethods' => true,
        'skipClosures' => false,
    ]);
};
