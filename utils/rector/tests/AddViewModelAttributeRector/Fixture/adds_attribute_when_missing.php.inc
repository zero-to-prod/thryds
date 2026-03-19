<?php
namespace ZeroToProd\Thryds\ViewModels;

readonly class UserViewModel
{
    use \ZeroToProd\Thryds\Attributes\DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}
?>
-----
<?php
namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Thryds\Attributes\ViewModel;
use ZeroToProd\Thryds\Attributes\DataModel;

#[ViewModel]
readonly class UserViewModel
{
    use DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}
?>
