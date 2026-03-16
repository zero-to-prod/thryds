<?php

use function ZeroToProd\Thryds\Helpers\short_class_name;

class NoViewKeyModel
{
    public string $name = '';
}

$data = [
    short_class_name(NoViewKeyModel::class) => new NoViewKeyModel(),
];
