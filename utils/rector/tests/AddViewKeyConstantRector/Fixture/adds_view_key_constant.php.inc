<?php

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\ViewModel;

#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;

    public string $message;
}

?>
-----
<?php

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\ViewModel;

#[ViewModel(key: 'ErrorViewModel')]
readonly class ErrorViewModel
{
    use DataModel;
    public const string view_key = 'ErrorViewModel';

    public string $message;
}

?>
