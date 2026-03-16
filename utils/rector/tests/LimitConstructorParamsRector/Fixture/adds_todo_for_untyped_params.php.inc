<?php

namespace Fixture;

class UntypedService
{
    public function __construct(
        private readonly $untyped1,
        private readonly $untyped2,
        private readonly $untyped3,
        private readonly $untyped4,
        private readonly $untyped5,
        private readonly $untyped6,
    ) {}
}

?>
-----
<?php

namespace Fixture;

class UntypedService
{
    // TODO: Too many constructor parameters (current: 6, max: 4)
    public function __construct(
        private readonly $untyped1,
        private readonly $untyped2,
        private readonly $untyped3,
        private readonly $untyped4,
        private readonly $untyped5,
        private readonly $untyped6,
    ) {}
}

?>
