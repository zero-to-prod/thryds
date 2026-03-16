<?php

$Closure = static function (): void {};

?>
-----
<?php

// TODO: [ForbidCallableTypeVariableNameRector] rename $Closure to describe its behaviour
$Closure = static function (): void {};

?>
