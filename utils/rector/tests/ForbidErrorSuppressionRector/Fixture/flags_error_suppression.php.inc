<?php

@file_get_contents('missing.txt');

?>
-----
<?php

// TODO: [opcache] @ error suppression adds per-call overhead — handle errors explicitly
@file_get_contents('missing.txt');

?>
