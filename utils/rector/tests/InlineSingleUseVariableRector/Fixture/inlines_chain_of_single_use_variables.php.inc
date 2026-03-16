<?php

$a = foo();
$b = bar($a);
$c = baz($b);

?>
-----
<?php

$c = baz(bar(foo()));

?>
