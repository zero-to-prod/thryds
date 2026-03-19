<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RemoveDefaultsAndApplyAtCallsiteRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RemoveDefaultsAndApplyAtCallsiteRector::class, [
        'mode' => 'auto',
        // Attributes are auto-discovered (empty = all). Opt-in functions/methods for fixture coverage.
        'targetFunctions' => ['Fixture\\greet'],
        'targetMethods' => ['Fixture\\Mailer::send'],
    ]);
};
