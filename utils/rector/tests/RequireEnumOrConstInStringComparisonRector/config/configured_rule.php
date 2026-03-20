<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireEnumOrConstInStringComparisonRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireEnumOrConstInStringComparisonRector::class, [
        'mode' => 'warn',
        'message' => "TODO: [RequireEnumOrConstInStringComparisonRector] Raw string '%s' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md",
    ]);
};
