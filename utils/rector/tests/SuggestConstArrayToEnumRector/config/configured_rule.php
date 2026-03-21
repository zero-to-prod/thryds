<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\SuggestConstArrayToEnumRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(SuggestConstArrayToEnumRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestConstArrayToEnumRector] Enumerations define sets — migrate const arrays to a backed enum with #[ClosedSet] and #[Group] attributes. See: utils/rector/docs/SuggestConstArrayToEnumRector.md',
    ]);
};
