<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RequirePersistsOnTableReferenceRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RequirePersistsOnTableReferenceRector::class, [
        'tablesNamespace'      => 'TestTables',
        'attributeClass'       => 'Persists',
        'controllersNamespace' => 'TestControllers',
        'mode'                 => 'warn',
        'message'              => "TODO: [RequirePersistsOnTableReferenceRector] '%s' imports '%s' from the tables namespace but is missing #[Persists(%s::class)].",
    ]);
};
