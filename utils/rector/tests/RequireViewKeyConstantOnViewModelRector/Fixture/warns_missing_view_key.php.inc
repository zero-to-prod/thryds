<?php

use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel]
readonly class UserViewModel
{
    public string $name;
}
-----
<?php

use ZeroToProd\Framework\Attributes\ViewModel;

// TODO: [RequireViewKeyConstantOnViewModelRector] UserViewModel is missing `public const string view_key`. Required for graph inventory.
#[ViewModel]
readonly class UserViewModel
{
    public string $name;
}
