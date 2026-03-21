<?php

namespace Fixture;

$a = doSomething('text/html');
$b = getHeader('text/html');
$c = setHeader('text/html');

?>
-----
<?php

namespace Fixture;

// TODO: [SuggestDuplicateStringConstantRector] Duplicate string 'text/html' (used 3x) — constants name things. Extract to a single source of truth. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
$a = doSomething('text/html');
$b = getHeader('text/html');
$c = setHeader('text/html');

?>
