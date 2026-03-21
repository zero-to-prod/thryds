<?php

namespace Utils\Rector\Tests\ForbidReflectionInInstanceMethodRector\Fixture;

class Inspector
{
    public function inspect(string $class, string $method): \ReflectionMethod
    {
        return new \ReflectionMethod($class, $method);
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\ForbidReflectionInInstanceMethodRector\Fixture;

class Inspector
{
    public function inspect(string $class, string $method): \ReflectionMethod
    {
        // TODO: Reflection on static class structure should be resolved at construction, not per-invocation. See: utils/rector/docs/ForbidReflectionInInstanceMethodRector.md
        return new \ReflectionMethod($class, $method);
    }
}

?>
