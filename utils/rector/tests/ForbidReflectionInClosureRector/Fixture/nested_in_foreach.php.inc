<?php

namespace Utils\Rector\Tests\ForbidReflectionInClosureRector\Fixture;

$handler = function (array $classes): array {
    foreach ($classes as $class) {
        $ref = new \ReflectionClass($class);
    }

    return [];
};

?>
-----
<?php

namespace Utils\Rector\Tests\ForbidReflectionInClosureRector\Fixture;

$handler = function (array $classes): array {
    // TODO: Reflection in closures runs per-invocation; hoist to the enclosing boot scope. See: utils/rector/docs/ForbidReflectionInClosureRector.md
    foreach ($classes as $class) {
        $ref = new \ReflectionClass($class);
    }

    return [];
};

?>
