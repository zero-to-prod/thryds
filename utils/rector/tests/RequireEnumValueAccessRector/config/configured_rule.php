<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequireEnumValueAccessRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequireEnumValueAccessRector::class, [
        'enumClasses' => [
            \Utils\Rector\Tests\RequireEnumValueAccessRector\TestView::class,
        ],
        'mode' => 'auto',
    ]);
};
