<?php

$Router->map('GET', $pattern, $handler);

?>
-----
<?php

// TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '(expression)' instead.
$Router->map('GET', $pattern, $handler);

?>
