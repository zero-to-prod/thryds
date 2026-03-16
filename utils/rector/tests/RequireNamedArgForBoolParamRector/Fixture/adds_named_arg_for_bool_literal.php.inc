<?php

function setCacheEntry(string $key, mixed $value, bool $compress = false): void {}

$value = 'data';
setCacheEntry('key', $value, true);

?>
-----
<?php

function setCacheEntry(string $key, mixed $value, bool $compress = false): void {}

$value = 'data';
setCacheEntry('key', $value, compress: true);

?>
