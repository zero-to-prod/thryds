<?php

class AppEnv {}

class Config
{
    /** @see $appEnv */
    public const string appEnv = 'appEnv';
    public AppEnv $appEnv;
}

?>
-----
<?php

class AppEnv {}

class Config
{
    /** @see $AppEnv */
    public const string AppEnv = 'AppEnv';
    public AppEnv $AppEnv;
}

?>
