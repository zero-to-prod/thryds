<?php

namespace Fixture;

function label(int $code): string
{
    switch ($code) {
        case 1:
            return 'One';
        case 2:
            return 'Two';
        case 3:
            return 'Three';
        default:
            return 'Other';
    }
}
?>
-----
<?php

namespace Fixture;

function label(int $code): string
{
    // TODO: [RequireEnumForBranchingConstantRector] $code is compared against 3 literals (1, 2, 3) — this is an implicit closed set. Consider extracting to a backed enum and using match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
    switch ($code) {
        case 1:
            return 'One';
        case 2:
            return 'Two';
        case 3:
            return 'Three';
        default:
            return 'Other';
    }
}
?>
