<?php

use Utils\Rector\Tests\RequireEnumValueAccessRector\TestView;

function make(string $view): void {}

make(\Utils\Rector\Tests\RequireEnumValueAccessRector\TestView::home->value);

?>
-----
<?php

use Utils\Rector\Tests\RequireEnumValueAccessRector\TestView;

function make(string $view): void {}

make(\Utils\Rector\Tests\RequireEnumValueAccessRector\TestView::home->value);

?>
