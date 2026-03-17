<?php

$result = 'prefix-' . \Utils\Rector\Tests\RequireEnumValueAccessRector\TestView::home;

?>
-----
<?php

$result = 'prefix-' . \Utils\Rector\Tests\RequireEnumValueAccessRector\TestView::home->value;

?>
