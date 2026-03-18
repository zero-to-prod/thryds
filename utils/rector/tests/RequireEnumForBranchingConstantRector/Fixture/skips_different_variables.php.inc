<?php

namespace Fixture;

function handle(string $a, string $b, string $c): string
{
    if ($a === 'active') {
        return 'A';
    } elseif ($b === 'inactive') {
        return 'B';
    } elseif ($c === 'pending') {
        return 'C';
    }
    return 'Unknown';
}
?>
-----
<?php

namespace Fixture;

function handle(string $a, string $b, string $c): string
{
    if ($a === 'active') {
        return 'A';
    } elseif ($b === 'inactive') {
        return 'B';
    } elseif ($c === 'pending') {
        return 'C';
    }
    return 'Unknown';
}
?>
