<?php

use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\ViewModel;

#[ViewModel(key: 'CompleteViewModel')]
readonly class CompleteViewModel
{
    use DataModel;

    public const string view_key = 'CompleteViewModel';

    public string $name;
}
