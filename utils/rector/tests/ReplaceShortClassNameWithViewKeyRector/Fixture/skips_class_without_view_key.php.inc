<?php


class NoViewKeyModel
{
    public string $name = '';
}

$data = [
    short_class_name(NoViewKeyModel::class) => new NoViewKeyModel(),
];
