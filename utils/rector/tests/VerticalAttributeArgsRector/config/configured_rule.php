<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\VerticalAttributeArgsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(VerticalAttributeArgsRector::class, [
        'minArgs' => 2,
        'mode' => 'auto',
    ]);
};
