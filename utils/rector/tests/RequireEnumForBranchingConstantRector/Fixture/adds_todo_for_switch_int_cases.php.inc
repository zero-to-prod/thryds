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
    // TODO: [RequireEnumForBranchingConstantRector] Enumerations define sets — $code is compared against 3 literals (1, 2, 3), forming an implicit closed set. Extract to a backed enum with #[ClosedSet] and use match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
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
