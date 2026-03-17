<?php

$a = ['status_code' => 200];
$b = ['status_code' => 404];

?>
-----
<?php

// TODO: [RequireConstForRepeatedArrayKeyRector] 'status_code' used 2x as array key — extract to a class constant.
$a = ['status_code' => 200];
$b = ['status_code' => 404];

?>
