<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use ReflectionClass;

trait HasTableName
{
    public static function tableName(): string
    {
        return new ReflectionClass(self::class)
            ->getAttributes(Table::class)[0]
            ->newInstance()
            ->TableName->value;
    }
}
