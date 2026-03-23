<?php
namespace ZeroToProd\Thryds\ViewModels;

readonly class UserViewModel
{
    use \ZeroToProd\Framework\Attributes\DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}
?>
-----
<?php
namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Framework\Attributes\ViewModel;
use ZeroToProd\Framework\Attributes\DataModel;

#[ViewModel]
readonly class UserViewModel
{
    use DataModel;
    public const string view_key = 'UserViewModel';
    public string $name;
}
?>
