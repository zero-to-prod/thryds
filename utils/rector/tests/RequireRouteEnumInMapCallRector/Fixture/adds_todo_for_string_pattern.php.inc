<?php

$Router->map('GET', '/posts/{post}', $handler);
$Router->map('POST', '/login', $handler);

?>
-----
<?php

// TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '/posts/{post}' instead.
$Router->map('GET', '/posts/{post}', $handler);
// TODO: [RequireRouteEnumInMapCallRector] Route pattern must use Route::case->value. Found '/login' instead.
$Router->map('POST', '/login', $handler);

?>
