<?php

$Router->map('GET', '/posts/{post}', $handler);

?>
-----
<?php

$Router->map('GET', \Fixture\Routes\PostsRoute::pattern, $handler);

?>
