<?php

namespace Fixture;

$a = doSomething('text/html');
$b = getHeader('text/html');
$c = setHeader('text/html');

?>
-----
<?php

namespace Fixture;

// TODO: Refactor duplicate string 'text/html' (used 3x) to a constant
$a = doSomething('text/html');
$b = getHeader('text/html');
$c = setHeader('text/html');

?>
