<?php

class Config
{
    public string $bladeCacheDir;
    public int $statusCode;

    public function getDirs(): string
    {
        return $this->bladeCacheDir;
    }

    public function getStatus(): int
    {
        return $this->statusCode;
    }
}

?>
-----
<?php

class Config
{
    public string $blade_cache_dir;
    public int $status_code;

    public function getDirs(): string
    {
        return $this->blade_cache_dir;
    }

    public function getStatus(): int
    {
        return $this->status_code;
    }
}

?>
