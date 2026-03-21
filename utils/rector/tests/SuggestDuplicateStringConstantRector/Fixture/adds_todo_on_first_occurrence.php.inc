<?php

namespace Fixture;

$a = doSomething('application/json');
$b = getHeader('application/json');

?>
-----
<?php

namespace Fixture;

// TODO: [SuggestDuplicateStringConstantRector] Duplicate string 'application/json' (used 2x) — constants name things. Extract to a single source of truth. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
$a = doSomething('application/json');
$b = getHeader('application/json');

?>
