<?php

use Utils\Rector\Tests\RequireEnumValueAccessRector\TestView;

function render(TestView $view): void {}

render(\Utils\Rector\Tests\RequireEnumValueAccessRector\TestView::home);

?>
-----
<?php

use Utils\Rector\Tests\RequireEnumValueAccessRector\TestView;

function render(TestView $view): void {}

render(\Utils\Rector\Tests\RequireEnumValueAccessRector\TestView::home);

?>
