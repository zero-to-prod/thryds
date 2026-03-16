<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function process($data) {
    return $data;
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

// TODO: Add param type for $data
function process($data) {
    return $data;
}

?>
