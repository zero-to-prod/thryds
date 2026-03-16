<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function calculate($rate = 0.5) {
    return $rate * 100;
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function calculate(float $rate = 0.5) {
    return $rate * 100;
}

?>
