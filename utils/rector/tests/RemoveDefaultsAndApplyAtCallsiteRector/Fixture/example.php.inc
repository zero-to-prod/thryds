<?php

namespace Fixture;

function greet(string $name, string $greeting = 'Hello'): string
{
    return "{$greeting}, {$name}!";
}

// Relies on the default — will have arg added.
greet('Alice');
// Already explicit positionally — untouched.
greet('Bob', 'Hi');
?>
-----
<?php

namespace Fixture;

function greet(string $name, string $greeting): string
{
    return "{$greeting}, {$name}!";
}

// Relies on the default — will have arg added.
greet('Alice', greeting: 'Hello');
// Already explicit positionally — untouched.
greet('Bob', 'Hi');
?>
