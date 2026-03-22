<?php

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\ViewModel;

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

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\ViewModel;

#[ViewModel(key: 'AlreadyHasKeyViewModel')]
readonly class AlreadyHasKeyViewModel
{
    use DataModel;

    public const string view_key = 'AlreadyHasKeyViewModel';

    public string $name;
}

?>
