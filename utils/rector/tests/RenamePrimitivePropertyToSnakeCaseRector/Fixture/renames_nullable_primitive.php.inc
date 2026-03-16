<?php

class Config
{
    public ?string $bladeCacheDir;

    public function getDir(): ?string
    {
        return $this->bladeCacheDir;
    }
}

?>
-----
<?php

class Config
{
    public ?string $blade_cache_dir;

    public function getDir(): ?string
    {
        return $this->blade_cache_dir;
    }
}

?>
