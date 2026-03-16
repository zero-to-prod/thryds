<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ExtractRoutePatternToRouteClassRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ExtractRoutePatternToRouteClassRector::class, [
        'methods' => ['map'],
        'argPosition' => 1,
        'namespace' => 'ZeroToProd\\Thryds\\Routes',
        'outputDir' => sys_get_temp_dir(),
    ]);
};
