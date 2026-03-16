<?php

use Zerotoprod\DataModel\DataModel;

class MethodAnnotationProfile
{
    use DataModel;

    public string $username;
    public int $age;
}

?>
-----
<?php

use Zerotoprod\DataModel\DataModel;

/**
 * @method static self from(array{username: string, age: int} $data)
 */
class MethodAnnotationProfile
{
    use DataModel;

    public string $username;
    public int $age;
}

?>
