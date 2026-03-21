<?php

namespace Utils\Rector\Tests\ForbidReflectionInClosureRector\Fixture;

$handler = function (object $model): array {
    // TODO: Reflection in closures runs per-invocation; hoist to the enclosing boot scope. See: utils/rector/docs/ForbidReflectionInClosureRector.md
    $ref = new \ReflectionClass($model);

    return $ref->getProperties();
};

?>
