<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

/** @param ?int $id */
function find($id) {
    return $id;
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

/** @param ?int $id */
function find(?int $id) {
    return $id;
}

?>
