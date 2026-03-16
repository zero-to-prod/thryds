<?php

namespace Test;

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class UserProfile
{
    use DataModel;

    #[Describe(['required' => true])]
    public string $name;
}

?>
-----
<?php

namespace Test;

use ZeroToProd\Thryds\Helpers\DataModel;
use ZeroToProd\Thryds\Helpers\Describe;

class UserProfile
{
    use DataModel;

    #[Describe(['required' => true])]
    public string $name;
}

?>
