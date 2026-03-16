<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function paginate($page = 1, $perPage = 25) {
    return [];
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

function paginate(int $page = 1, int $perPage = 25) {
    return [];
}

?>
