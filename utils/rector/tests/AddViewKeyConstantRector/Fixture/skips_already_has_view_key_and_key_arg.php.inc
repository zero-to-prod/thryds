<?php

use ZeroToProd\Framework\Attributes\DataModel;
use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel(key: 'CompleteViewModel')]
readonly class CompleteViewModel
{
    use DataModel;

    public const string view_key = 'CompleteViewModel';

    public string $name;
}
