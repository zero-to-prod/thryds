<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RemoveDefaultsAndApplyAtCallsiteRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RemoveDefaultsAndApplyAtCallsiteRector::class, [
        'mode' => 'auto',
        // Provide all three target keys (even as empty) so noopMode is false.
        // Each fixture sets its own config, but this base config activates the rule
        // by declaring that it should match all functions/methods/attributes found.
        'targetFunctions' => [],
        'targetMethods' => [],
        'targetAttributes' => [],
    ]);
};
