<?php

$a = $data['opcache_statistics']['hits'];
$b = $data['opcache_statistics']['misses'];

?>
-----
<?php

// TODO: [RequireConstForRepeatedArrayKeyRector] 'opcache_statistics' used 2x as array key — extract to a class constant.
$a = $data['opcache_statistics']['hits'];
$b = $data['opcache_statistics']['misses'];

?>
