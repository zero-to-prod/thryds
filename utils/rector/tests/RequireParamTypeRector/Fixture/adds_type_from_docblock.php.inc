<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

/** @param string $name */
function greet($name) {
    return "Hello, $name";
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

/** @param string $name */
function greet(string $name) {
    return "Hello, $name";
}

?>
