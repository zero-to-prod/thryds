<?php

$Router->map('GET', SomeClass::PATTERN, $handler);

?>
-----
<?php

// TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found 'SomeClass::PATTERN' instead.
$Router->map('GET', SomeClass::PATTERN, $handler);

?>
