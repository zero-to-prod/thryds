<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\EnforceLayerCoverageRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(EnforceLayerCoverageRector::class, [
        'layerEnum' => 'Layer',
        'segmentAttribute' => 'Segment',
        'srcDir' => __DIR__ . '/../Fixture/fake_src',
        'mode' => 'warn',
    ]);
};
