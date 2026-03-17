<?php

namespace Utils\Rector\Tests\RemoveNamedArgWhenVarMatchesParamRector\Fixture;

use App\Domain;

#[\Attribute]
class ClosedSet {
    public function __construct(public string $Domain) {}
}

#[ClosedSet(Domain: Domain::url_routes)]
enum Route: string {}

?>
-----
<?php

namespace Utils\Rector\Tests\RemoveNamedArgWhenVarMatchesParamRector\Fixture;

use App\Domain;

#[\Attribute]
class ClosedSet {
    public function __construct(public string $Domain) {}
}

#[ClosedSet(Domain::url_routes)]
enum Route: string {}

?>