<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ValidateSourceOfTruthConsumersRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ValidateSourceOfTruthConsumersRector::class, [
        'attributeClass' => 'Utils\\Rector\\Tests\\ValidateSourceOfTruthConsumersRector\\TestSourceOfTruth',
        'mode' => 'warn',
        'message' => 'TODO: [ValidateSourceOfTruthConsumersRector] %s declares %s as a consumer, but it does not reference %s. Update the consumers list.',
        'projectDir' => __DIR__ . '/../',
        'psr4Map' => [
            'Utils\\Rector\\Tests\\ValidateSourceOfTruthConsumersRector\\' => __DIR__ . '/../Support/',
        ],
    ]);
};
