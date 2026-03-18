<?php

namespace Fixture;

function handle(string $status): string
{
    // TODO: [RequireEnumForBranchingConstantRector] $status is compared against 3 literals ('active', 'inactive', 'pending') — this is an implicit closed set. Consider extracting to a backed enum and using match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
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
    // TODO: [RequireEnumForBranchingConstantRector] $status is compared against 3 literals ('active', 'inactive', 'pending') — this is an implicit closed set. Consider extracting to a backed enum and using match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
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
