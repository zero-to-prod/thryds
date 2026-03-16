<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RenameEnumCaseToMatchValueRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(RenameEnumCaseToMatchValueRector::class);
};
