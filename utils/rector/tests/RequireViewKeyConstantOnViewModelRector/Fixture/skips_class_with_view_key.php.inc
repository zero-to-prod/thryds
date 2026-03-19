<?php

use ZeroToProd\Thryds\Attributes\ViewModel;

#[ViewModel]
readonly class UserViewModel
{
    public const string view_key = 'UserViewModel';
    public string $name;
}
