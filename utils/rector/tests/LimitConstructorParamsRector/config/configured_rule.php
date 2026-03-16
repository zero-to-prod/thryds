<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\LimitConstructorParamsRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(LimitConstructorParamsRector::class, [
        'maxParams' => 4,
        'dtoSuffix' => 'Deps',
        'dtoOutputDir' => sys_get_temp_dir(),
    ]);
};
