<?php

namespace Fixture;

use Utils\Rector\Tests\RemoveDefaultsAndApplyAtCallsiteRector\Support\TestKeyRegistry;

// Omits superglobals and addKey — both will be added.
#[TestKeyRegistry('vite_entry_points')]
class PageController {}

// Already explicit for superglobals — only addKey will be added.
#[TestKeyRegistry('other', superglobals: ['_SERVER'])]
class OtherController {}

// Already explicit for both — untouched.
#[TestKeyRegistry('explicit', superglobals: [], addKey: 'do something')]
class FullController {}
?>
-----
<?php

namespace Fixture;

use Utils\Rector\Tests\RemoveDefaultsAndApplyAtCallsiteRector\Support\TestKeyRegistry;

// Omits superglobals and addKey — both will be added.
#[TestKeyRegistry('vite_entry_points', superglobals: [], addKey: '')]
class PageController {}

// Already explicit for superglobals — only addKey will be added.
#[TestKeyRegistry('other', superglobals: ['_SERVER'], addKey: '')]
class OtherController {}

// Already explicit for both — untouched.
#[TestKeyRegistry('explicit', superglobals: [], addKey: 'do something')]
class FullController {}
?>
