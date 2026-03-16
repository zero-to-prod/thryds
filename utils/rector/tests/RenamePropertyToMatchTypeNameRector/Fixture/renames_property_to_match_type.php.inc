<?php

class AppEnv {}

class Config
{
    public AppEnv $appEnv;

    public function isProduction(): bool
    {
        return $this->appEnv instanceof AppEnv;
    }
}

?>
-----
<?php

class AppEnv {}

class Config
{
    public AppEnv $AppEnv;

    public function isProduction(): bool
    {
        return $this->AppEnv instanceof AppEnv;
    }
}

?>
