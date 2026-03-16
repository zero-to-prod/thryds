<?php

namespace Fixture;

class SmallService
{
    public function __construct(
        private readonly RepoA $RepoA,
        private readonly RepoB $RepoB,
        private readonly RepoC $RepoC,
    ) {}
}

?>
