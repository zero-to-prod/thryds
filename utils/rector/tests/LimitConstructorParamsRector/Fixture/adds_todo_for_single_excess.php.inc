<?php

namespace Fixture;

class SingleExcess
{
    public function __construct(
        private readonly RepoA $RepoA,
        private readonly RepoB $RepoB,
        private readonly RepoC $RepoC,
        private readonly RepoD $RepoD,
        private readonly RepoE $RepoE,
    ) {}
}

?>
-----
<?php

namespace Fixture;

class SingleExcess
{
    // TODO: Too many constructor parameters (current: 5, max: 4)
    public function __construct(
        private readonly RepoA $RepoA,
        private readonly RepoB $RepoB,
        private readonly RepoC $RepoC,
        private readonly RepoD $RepoD,
        private readonly RepoE $RepoE,
    ) {}
}

?>
