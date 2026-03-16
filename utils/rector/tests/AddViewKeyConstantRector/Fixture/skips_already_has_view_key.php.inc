<?php

use ZeroToProd\Thryds\Helpers\DataModel;
use ZeroToProd\Thryds\Helpers\ViewModel;

#[ViewModel]
readonly class AlreadyHasKeyViewModel
{
    use DataModel;

    public const string view_key = 'AlreadyHasKeyViewModel';

    public string $name;
}
