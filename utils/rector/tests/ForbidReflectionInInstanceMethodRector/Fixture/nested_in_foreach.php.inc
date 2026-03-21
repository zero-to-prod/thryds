<?php

namespace Utils\Rector\Tests\ForbidReflectionInInstanceMethodRector\Fixture;

class Handler
{
    public function handle(\Throwable $Throwable): void
    {
        foreach (new \ReflectionClass(self::class)->getMethods() as $Method) {
            $Method->invoke($this, $Throwable);
        }
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\ForbidReflectionInInstanceMethodRector\Fixture;

class Handler
{
    public function handle(\Throwable $Throwable): void
    {
        // TODO: Reflection on static class structure should be resolved at construction, not per-invocation. See: utils/rector/docs/ForbidReflectionInInstanceMethodRector.md
        foreach (new \ReflectionClass(self::class)->getMethods() as $Method) {
            $Method->invoke($this, $Throwable);
        }
    }
}

?>
