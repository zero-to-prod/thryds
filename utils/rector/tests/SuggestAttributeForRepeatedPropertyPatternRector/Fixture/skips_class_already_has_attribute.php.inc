<?php

use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestDataModel;
use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestViewModel;

#[TestViewModel]
class ErrorViewModel
{
    use TestDataModel;
    public const string view_key = 'ErrorViewModel';
}

?>
-----
<?php

use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestDataModel;
use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestViewModel;

#[TestViewModel]
class ErrorViewModel
{
    use TestDataModel;
    public const string view_key = 'ErrorViewModel';
}

?>
