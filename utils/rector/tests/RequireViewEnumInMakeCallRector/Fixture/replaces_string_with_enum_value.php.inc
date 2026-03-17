<?php

$Blade->make(view: 'home');

?>
-----
<?php

$Blade->make(view: \Utils\Rector\Tests\RequireViewEnumInMakeCallRector\TestView::home->value);

?>
