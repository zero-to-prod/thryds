<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireHandlesExceptionOnPublicHandlerMethodRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireHandlesExceptionOnPublicHandlerMethodRector::class, [
        'mode' => 'warn',
        'handlerAttributeClass' => 'Utils\\Rector\\Tests\\RequireHandlesExceptionOnPublicHandlerMethodRector\\Source\\TestHandlesException',
        'throwableClass' => 'Throwable',
        'excludeMethods' => ['handle'],
    ]);
};
