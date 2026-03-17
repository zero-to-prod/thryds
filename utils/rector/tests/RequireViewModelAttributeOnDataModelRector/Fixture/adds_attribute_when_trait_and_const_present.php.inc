<?php

use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestDataModel;

readonly class ProfileViewModel
{
    use TestDataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}

?>
-----
<?php

use Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestDataModel;

#[\Utils\Rector\Tests\RequireViewModelAttributeOnDataModelRector\TestViewModel]
readonly class ProfileViewModel
{
    use TestDataModel;
    public const string view_key = 'ProfileViewModel';
    public string $name;
}

?>
