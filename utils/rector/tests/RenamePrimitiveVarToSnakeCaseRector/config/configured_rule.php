<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RenamePrimitiveVarToSnakeCaseRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(RenamePrimitiveVarToSnakeCaseRector::class);
};
