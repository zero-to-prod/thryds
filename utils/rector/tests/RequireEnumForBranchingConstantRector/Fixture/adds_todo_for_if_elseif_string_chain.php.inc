<?php

namespace Fixture;

function handle(string $status): string
{
    if ($status === 'active') {
        return 'Active';
    } elseif ($status === 'inactive') {
        return 'Inactive';
    } elseif ($status === 'pending') {
        return 'Pending';
    }
    return 'Unknown';
}
?>
-----
<?php

namespace Fixture;

function handle(string $status): string
{
    // TODO: [RequireEnumForBranchingConstantRector] Enumerations define sets — $status is compared against 3 literals ('active', 'inactive', 'pending'), forming an implicit closed set. Extract to a backed enum with #[ClosedSet] and use match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
    if ($status === 'active') {
        return 'Active';
    } elseif ($status === 'inactive') {
        return 'Inactive';
    } elseif ($status === 'pending') {
        return 'Pending';
    }
    return 'Unknown';
}
?>
