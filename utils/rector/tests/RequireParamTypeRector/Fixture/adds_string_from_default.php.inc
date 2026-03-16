<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function greet($name = 'world') {
    return 'Hello, ' . $name;
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function greet(string $name = 'world') {
    return 'Hello, ' . $name;
}

?>
