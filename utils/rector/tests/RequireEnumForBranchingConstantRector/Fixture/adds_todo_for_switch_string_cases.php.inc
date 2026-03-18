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
    // TODO: [RequireEnumForBranchingConstantRector] $color is compared against 3 literals ('red', 'green', 'blue') — this is an implicit closed set. Consider extracting to a backed enum and using match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
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
