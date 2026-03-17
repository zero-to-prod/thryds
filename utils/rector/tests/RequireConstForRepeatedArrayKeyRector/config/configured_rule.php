<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireConstForRepeatedArrayKeyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireConstForRepeatedArrayKeyRector::class, [
        'minOccurrences' => 2,
        'minLength' => 3,
        'excludedKeys' => ['class', 'mode', 'message'],
        'excludedClasses' => ['SomeExcludedClass'],
        'mode' => 'warn',
        'message' => "TODO: [RequireConstForRepeatedArrayKeyRector] '%s' used %dx as array key — extract to a class constant.",
    ]);
};
