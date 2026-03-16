<?php

class AppEnv {}

class Config
{
    public ?AppEnv $appEnv;

    public function get(): ?AppEnv
    {
        return $this->appEnv;
    }
}

?>
-----
<?php

class AppEnv {}

class Config
{
    public ?AppEnv $AppEnv;

    public function get(): ?AppEnv
    {
        return $this->AppEnv;
    }
}

?>
