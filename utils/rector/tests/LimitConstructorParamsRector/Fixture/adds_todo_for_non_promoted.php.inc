<?php

namespace Fixture;

class NonPromotedService
{
    private RepoA $RepoA;
    private RepoB $RepoB;

    public function __construct(
        RepoA $RepoA,
        RepoB $RepoB,
        private readonly RepoC $RepoC,
        private readonly RepoD $RepoD,
        private readonly RepoE $RepoE,
        private readonly RepoF $RepoF,
    ) {
        $this->RepoA = $RepoA;
        $this->RepoB = $RepoB;
    }
}

?>
-----
<?php

namespace Fixture;

class NonPromotedService
{
    private RepoA $RepoA;
    private RepoB $RepoB;

    // TODO: Too many constructor parameters (current: 6, max: 4)
    public function __construct(
        RepoA $RepoA,
        RepoB $RepoB,
        private readonly RepoC $RepoC,
        private readonly RepoD $RepoD,
        private readonly RepoE $RepoE,
        private readonly RepoF $RepoF,
    ) {
        $this->RepoA = $RepoA;
        $this->RepoB = $RepoB;
    }
}

?>
