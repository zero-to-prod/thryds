<?php

$result = $status !== 'active';

?>
-----
<?php

// TODO: [RequireEnumOrConstInStringComparisonRector] Raw string 'active' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md
$result = $status !== 'active';

?>
