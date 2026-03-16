<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\ForbidMagicStringArrayKeyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ForbidMagicStringArrayKeyRector::class);
};
