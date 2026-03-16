<?php

use ZeroToProd\Thryds\Helpers\DataModel;
use ZeroToProd\Thryds\Helpers\ViewModel;

#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;

    public string $message;
}

?>
-----
<?php

use ZeroToProd\Thryds\Helpers\DataModel;
use ZeroToProd\Thryds\Helpers\ViewModel;

#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;
    public const string view_key = 'ErrorViewModel';

    public string $message;
}

?>
