<?php

use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestDataModel;

readonly class Config
{
    use TestDataModel;
    public const string AppEnv = 'AppEnv';
    public string $blade_cache_dir;
}
