<?php

namespace Fixture;

$a = doSomething('application/json');
$b = getHeader('application/json');

?>
-----
<?php

namespace Fixture;

// TODO: Refactor duplicate string 'application/json' (used 2x) to a constant
$a = doSomething('application/json');
$b = getHeader('application/json');

?>
