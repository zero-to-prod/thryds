<?php

if ($request->getMethod() === 'POST') {
    echo 'post';
}

?>
-----
<?php

// TODO: [RequireEnumOrConstInStringComparisonRector] Raw string 'POST' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md
if ($request->getMethod() === 'POST') {
    echo 'post';
}

?>
