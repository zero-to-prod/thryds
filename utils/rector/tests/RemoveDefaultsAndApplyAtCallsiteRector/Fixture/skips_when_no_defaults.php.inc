<?php

namespace Fixture;

function compute(string $a, string $b): string
{
    return $a . $b;
}

compute('x', 'y');
?>
-----
<?php

namespace Fixture;

function compute(string $a, string $b): string
{
    return $a . $b;
}

compute('x', 'y');
?>
