<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireSpecificResponseReturnTypeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->importNames();
    $rectorConfig->ruleWithConfiguration(RequireSpecificResponseReturnTypeRector::class, [
        'controllerNamespaces' => [
            'Utils\Rector\Tests\RequireSpecificResponseReturnTypeRector\AutoFixture',
        ],
        'genericInterface' => 'Psr\Http\Message\ResponseInterface',
        'mode' => 'auto',
    ]);
};
