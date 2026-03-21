<?php

namespace Fixture;

function describe(string $color): string
{
    switch ($color) {
        case 'red':
            return 'Red';
        case 'green':
            return 'Green';
        case 'blue':
            return 'Blue';
        default:
            return 'Unknown';
    }
}
?>
-----
<?php

namespace Fixture;

function describe(string $color): string
{
    // TODO: [RequireEnumForBranchingConstantRector] Enumerations define sets — $color is compared against 3 literals ('red', 'green', 'blue'), forming an implicit closed set. Extract to a backed enum with #[ClosedSet] and use match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
    switch ($color) {
        case 'red':
            return 'Red';
        case 'green':
            return 'Green';
        case 'blue':
            return 'Blue';
        default:
            return 'Unknown';
    }
}
?>
