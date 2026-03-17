<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireSpecificResponseReturnTypeRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireSpecificResponseReturnTypeRector::class, [
        'controllerNamespaces' => [
            'Utils\Rector\Tests\RequireSpecificResponseReturnTypeRector\Fixture',
        ],
        'genericInterface' => 'Psr\Http\Message\ResponseInterface',
        'mode' => 'warn',
        'message' => 'TODO: [RequireSpecificResponseReturnTypeRector] Replace generic ResponseInterface return type with the specific response class actually returned (e.g. HtmlResponse or JsonResponse).',
    ]);
};
