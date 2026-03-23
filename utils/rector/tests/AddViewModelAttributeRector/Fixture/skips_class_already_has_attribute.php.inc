<?php
namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel]
readonly class ErrorViewModel
{
    use \ZeroToProd\Framework\Attributes\DataModel;
    public const string view_key = 'ErrorViewModel';
    public string $message;
}
?>
-----
<?php
namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Framework\Attributes\DataModel;
use ZeroToProd\Framework\Attributes\ViewModel;

#[ViewModel]
readonly class ErrorViewModel
{
    use DataModel;
    public const string view_key = 'ErrorViewModel';
    public string $message;
}
?>
