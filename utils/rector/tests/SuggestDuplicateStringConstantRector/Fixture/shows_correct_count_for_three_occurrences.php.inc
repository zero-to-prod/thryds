<?php

namespace Fixture;

$a = doSomething('text/html');
$b = getHeader('text/html');
$c = setHeader('text/html');

?>
-----
<?php

namespace Fixture;

// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'text/html' (used 3x) to a single source of truth. Consts name things, enums limit choices, attributes define properties.
$a = doSomething('text/html');
$b = getHeader('text/html');
$c = setHeader('text/html');

?>
