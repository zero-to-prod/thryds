<?php

use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestDataModel;

class UserViewModel
{
    use TestDataModel;
    public const string view_key = 'UserViewModel';
}

?>
-----
<?php

use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestViewModel;
use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestDataModel;

#[TestViewModel]
class UserViewModel
{
    use TestDataModel;
    public const string view_key = 'UserViewModel';
}

?>
