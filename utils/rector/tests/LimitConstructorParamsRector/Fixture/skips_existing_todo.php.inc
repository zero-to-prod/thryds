<?php

namespace Fixture;

class AlreadyAnnotated
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
