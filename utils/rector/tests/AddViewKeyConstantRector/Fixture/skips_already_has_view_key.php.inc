<?php

use ZeroToProd\Framework\Attributes\DataModel;
use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel]
readonly class AlreadyHasKeyViewModel
{
    use DataModel;

    public const string view_key = 'AlreadyHasKeyViewModel';

    public string $name;
}

?>
-----
<?php

use ZeroToProd\Framework\Attributes\DataModel;
use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel(key: 'AlreadyHasKeyViewModel')]
readonly class AlreadyHasKeyViewModel
{
    use DataModel;

    public const string view_key = 'AlreadyHasKeyViewModel';

    public string $name;
}

?>
