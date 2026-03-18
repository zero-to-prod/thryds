<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidEnvCheckOutsideConfigRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidEnvCheckOutsideConfigRector::class, [
        'mode' => 'warn',
        'message' => 'TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md',
    ]);
};
