<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function process($items = []) {
    return $items;
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function process(array $items = []) {
    return $items;
}

?>
