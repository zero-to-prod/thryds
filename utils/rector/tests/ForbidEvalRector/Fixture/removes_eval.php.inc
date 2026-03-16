<?php

$value = 'test';
eval('echo "hello";');
echo $value;

?>
-----
<?php

$value = 'test';
echo $value;

?>
