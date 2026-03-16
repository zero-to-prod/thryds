<?php

namespace Fixture;

class BodyLogicService
{
    public function __construct(
        private readonly RepoA $RepoA,
        private readonly RepoB $RepoB,
        private readonly RepoC $RepoC,
        private readonly RepoD $RepoD,
        private readonly RepoE $RepoE,
        private readonly RepoF $RepoF,
    ) {
        $this->validate();
    }

    private function validate(): void {}
}

?>
-----
<?php

namespace Fixture;

class BodyLogicService
{
    // TODO: Too many constructor parameters (current: 6, max: 4)
    public function __construct(
        private readonly RepoA $RepoA,
        private readonly RepoB $RepoB,
        private readonly RepoC $RepoC,
        private readonly RepoD $RepoD,
        private readonly RepoE $RepoE,
        private readonly RepoF $RepoF,
    ) {
        $this->validate();
    }

    private function validate(): void {}
}

?>
