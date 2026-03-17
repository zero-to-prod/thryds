<?php

use Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector\TestViewModel;

#[TestViewModel]
readonly class ErrorViewModel
{
    public const string view_key = 'ErrorViewModel';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector\TestViewModel;

#[TestViewModel]
readonly class ErrorViewModel
{
    public const string view_key = 'ErrorViewModel';
}

?>
