<?php

$Router->map('GET', '/posts/{post}', $handler);
$Router->map('POST', '/login', $handler);

?>
-----
<?php

// TODO: Extract route pattern '/posts/{post}' to a class constant
$Router->map('GET', '/posts/{post}', $handler);
// TODO: Extract route pattern '/login' to a class constant
$Router->map('POST', '/login', $handler);

?>
