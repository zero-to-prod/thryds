<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function toggle($enabled = false) {
    return $enabled;
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function toggle(bool $enabled = false) {
    return $enabled;
}

?>
