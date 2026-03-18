<?php

function processString(string $value): string
{
    return match($value) {
        'active' => 'Active',
        'inactive' => 'Inactive',
        default => 'Other',
    };
}

?>
-----
<?php

function processString(string $value): string
{
    return match($value) {
        'active' => 'Active',
        'inactive' => 'Inactive',
        default => 'Other',
    };
}

?>
