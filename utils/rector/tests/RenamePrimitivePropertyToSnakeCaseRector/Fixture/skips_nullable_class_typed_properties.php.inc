<?php

class StorageEngine {}

class TableAttr
{
    public ?StorageEngine $Engine;
}

class DdlGen
{
    public function generate(TableAttr $Table): string
    {
        return $Table->Engine !== null ? $Table->Engine::class : '';
    }
}

?>
