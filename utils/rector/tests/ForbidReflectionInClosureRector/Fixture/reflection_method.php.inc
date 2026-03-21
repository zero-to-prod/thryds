<?php

namespace Utils\Rector\Tests\ForbidReflectionInClosureRector\Fixture;

$callback = static function (string $class): array {
    $ref = new \ReflectionMethod($class, 'handle');

    return $ref->getAttributes();
};

?>
-----
<?php

namespace Utils\Rector\Tests\ForbidReflectionInClosureRector\Fixture;

$callback = static function (string $class): array {
    // TODO: Reflection in closures runs per-invocation; hoist to the enclosing boot scope. See: utils/rector/docs/ForbidReflectionInClosureRector.md
    $ref = new \ReflectionMethod($class, 'handle');

    return $ref->getAttributes();
};

?>
