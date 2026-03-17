<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireFragmentIfForBladeRenderRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireFragmentIfForBladeRenderRector::class, [
        'mode' => 'warn',
    ]);
};
