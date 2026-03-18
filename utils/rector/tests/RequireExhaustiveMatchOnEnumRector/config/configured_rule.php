<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireExhaustiveMatchOnEnumRector;

require_once __DIR__ . '/../Support/TestStatus.php';

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireExhaustiveMatchOnEnumRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [RequireExhaustiveMatchOnEnumRector] match() on %s must cover all cases explicitly.',
    ]);
};
