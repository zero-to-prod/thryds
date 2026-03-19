<?php

$Blade->make(view: \Utils\Rector\Tests\RequireViewModelDataInMakeCallRector\Support\TestView::register->value);

?>
-----
<?php

// TODO: [RequireViewModelDataInMakeCallRector] make() renders 'register' which has a RegisterViewModel — add data: argument.
$Blade->make(view: \Utils\Rector\Tests\RequireViewModelDataInMakeCallRector\Support\TestView::register->value);

?>
