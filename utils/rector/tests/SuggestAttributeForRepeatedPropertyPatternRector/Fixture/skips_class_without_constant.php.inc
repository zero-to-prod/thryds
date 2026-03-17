<?php

use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestDataModel;

class NoConstClass
{
    use TestDataModel;
}

?>
-----
<?php

use Utils\Rector\Tests\SuggestAttributeForRepeatedPropertyPatternRector\TestDataModel;

class NoConstClass
{
    use TestDataModel;
}

?>
