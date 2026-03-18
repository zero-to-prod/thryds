<?php

namespace Fixture;

function route(int $code): string
{
    if ($code === 1) {
        return 'home';
    } elseif ($code === 2) {
        return 'about';
    } elseif ($code === 3) {
        return 'contact';
    }
    return 'unknown';
}
?>
-----
<?php

namespace Fixture;

function route(int $code): string
{
    // TODO: [RequireEnumForBranchingConstantRector] $code is compared against 3 literals (1, 2, 3) — this is an implicit closed set. Consider extracting to a backed enum and using match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
    if ($code === 1) {
        return 'home';
    } elseif ($code === 2) {
        return 'about';
    } elseif ($code === 3) {
        return 'contact';
    }
    return 'unknown';
}
?>
