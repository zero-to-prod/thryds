<?php

namespace Fixture;

function handle(string $status): string
{
    switch ($status) {
        case 'active':
            return 'Active';
        case 'inactive':
            return 'Inactive';
        default:
            return 'Unknown';
    }
}
?>
-----
<?php

namespace Fixture;

function handle(string $status): string
{
    switch ($status) {
        case 'active':
            return 'Active';
        case 'inactive':
            return 'Inactive';
        default:
            return 'Unknown';
    }
}
?>
