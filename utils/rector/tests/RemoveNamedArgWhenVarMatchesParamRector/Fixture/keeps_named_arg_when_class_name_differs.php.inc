<?php

namespace Utils\Rector\Tests\RemoveNamedArgWhenVarMatchesParamRector\Fixture;

use App\Domain;

// Named arg "source" does not match class name "Domain" — keep it
#[ClosedSet(source: Domain::url_routes)]
enum Route: string {}