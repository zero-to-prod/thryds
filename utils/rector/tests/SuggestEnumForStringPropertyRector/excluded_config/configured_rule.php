<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\SuggestEnumForStringPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(SuggestEnumForStringPropertyRector::class, [
        'dataModelTraits' => ['Zerotoprod\DataModel\DataModel'],
        'describeAttrs' => ['Zerotoprod\DataModel\Describe'],
        'excludedFiles' => ['skips_excluded_file.php'],
    ]);
};
