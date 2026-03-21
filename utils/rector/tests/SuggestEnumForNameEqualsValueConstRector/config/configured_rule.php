<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\SuggestEnumForNameEqualsValueConstRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(SuggestEnumForNameEqualsValueConstRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [SuggestEnumForNameEqualsValueConstRector] Enumerations define sets — %s has %d string constants where name equals value. Migrate to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForNameEqualsValueConstRector.md',
        'minConstants' => 2,
        'excludedAttributes' => [
            'Utils\\Rector\\Tests\\SuggestEnumForNameEqualsValueConstRector\\TestClosedSet',
        ],
    ]);
};
