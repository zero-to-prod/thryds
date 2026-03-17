<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidBareServerEnvKeyRector;

require_once __DIR__ . '/../Support/Env.php';

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidBareServerEnvKeyRector::class, [
        'envClass' => 'Utils\\Rector\\Tests\\ForbidBareServerEnvKeyRector\\Env',
        'superglobals' => ['_SERVER', '_ENV'],
        'mode' => 'auto',
    ]);
};
