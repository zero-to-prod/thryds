<?php

use Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector\TestDataModel;

readonly class Config
{
    use TestDataModel;
    public const string app_env = 'app_env';
    public const string cache_dir = 'cache_dir';
    public const string template_dir = 'template_dir';
    public string $app_env;
}

?>
-----
<?php

use Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector\TestDataModel;

readonly class Config
{
    use TestDataModel;
    public const string app_env = 'app_env';
    public const string cache_dir = 'cache_dir';
    public const string template_dir = 'template_dir';
    public string $app_env;
}

?>
