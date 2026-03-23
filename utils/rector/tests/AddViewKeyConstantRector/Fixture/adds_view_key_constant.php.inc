<?php

use ZeroToProd\Framework\Attributes\DataModel;
use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;

    public string $message;
}

?>
-----
<?php

use ZeroToProd\Framework\Attributes\DataModel;
use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel(key: 'ErrorViewModel')]
readonly class ErrorViewModel
{
    use DataModel;
    public const string view_key = 'ErrorViewModel';

    public string $message;
}

?>
