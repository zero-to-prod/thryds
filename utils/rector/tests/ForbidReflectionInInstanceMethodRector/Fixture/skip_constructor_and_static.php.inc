<?php

namespace Utils\Rector\Tests\ForbidReflectionInInstanceMethodRector\Fixture;

class BootService
{
    private array $fields;

    public function __construct(string $class)
    {
        $ref = new \ReflectionClass($class);
        $this->fields = $ref->getProperties();
    }

    public static function reflect(string $class): array
    {
        return (new \ReflectionClass($class))->getMethods();
    }
}

?>
