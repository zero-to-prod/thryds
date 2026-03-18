<?php

namespace Fixture;

function handle(string $status): string
{
    if ($status === 'active') {
        return 'Active';
    } elseif ($status === 'inactive') {
        return 'Inactive';
    }
    return 'Unknown';
}
?>
-----
<?php

namespace Fixture;

function handle(string $status): string
{
    if ($status === 'active') {
        return 'Active';
    } elseif ($status === 'inactive') {
        return 'Inactive';
    }
    return 'Unknown';
}
?>
