<?php

namespace Fixture;

use Attribute;

#[Attribute]
class KeyRegistry
{
    public function __construct(
        public readonly string $source,
        public readonly string $addKey = '1. Add constant. 2. Register directive.',
    ) {}
}
?>
-----
<?php

namespace Fixture;

use Attribute;

#[Attribute]
class KeyRegistry
{
    public function __construct(
        public readonly string $source,
        public readonly string $addKey,
    ) {}
}
?>
