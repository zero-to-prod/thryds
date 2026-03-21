<?php

namespace Utils\Rector\Tests\ForbidReflectionInClosureRector\Fixture;

$handler = function (string $class): object {
    return new \stdClass();
};

?>
