<?php

$Router->map('GET', '/posts/{post}', $handler);

?>
-----
<?php

$Router->map('GET', \ZeroToProd\Thryds\Routes\PostsRoute::pattern, $handler);

?>
