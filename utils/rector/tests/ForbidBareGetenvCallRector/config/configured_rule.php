<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidBareGetenvCallRector;

require_once __DIR__ . '/../Support/Env.php';

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(ForbidBareGetenvCallRector::class, [
        'envClass' => 'Utils\\Rector\\Tests\\ForbidBareGetenvCallRector\\Env',
        'functions' => ['getenv'],
        'mode' => 'auto',
    ]);
};
