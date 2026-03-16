<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\SuggestDuplicateStringConstantRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(SuggestDuplicateStringConstantRector::class);
};
