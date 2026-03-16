<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireRouteEnumInMapCallRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireRouteEnumInMapCallRector::class, [
        'enumClass' => 'ZeroToProd\\Thryds\\Routes\\Route',
        'methods' => ['map'],
        'argPosition' => 1,
        'mode' => 'warn',
        'message' => "TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '%s' instead.",
    ]);
};
