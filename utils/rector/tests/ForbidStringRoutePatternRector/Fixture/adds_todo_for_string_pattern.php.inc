<?php

$Router->map('GET', '/posts/{post}', $handler);
$Router->map('POST', '/login', $handler);

?>
-----
<?php

// TODO: [ForbidStringRoutePatternRector] Route patterns must be class constant references, not inline strings. Extract '/posts/{post}' to a Route class constant.
$Router->map('GET', '/posts/{post}', $handler);
// TODO: [ForbidStringRoutePatternRector] Route patterns must be class constant references, not inline strings. Extract '/login' to a Route class constant.
$Router->map('POST', '/login', $handler);

?>
