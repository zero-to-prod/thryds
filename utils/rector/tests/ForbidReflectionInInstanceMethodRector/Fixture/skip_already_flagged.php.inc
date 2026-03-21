<?php

namespace Utils\Rector\Tests\ForbidReflectionInInstanceMethodRector\Fixture;

class AlreadyFlagged
{
    public function validate(object $model): array
    {
        // TODO: Reflection on static class structure should be resolved at construction, not per-invocation. See: utils/rector/docs/ForbidReflectionInInstanceMethodRector.md
        $ref = new \ReflectionClass($model);

        return [];
    }
}

?>
