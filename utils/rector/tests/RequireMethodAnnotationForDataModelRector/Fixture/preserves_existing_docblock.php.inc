<?php

use Zerotoprod\DataModel\DataModel;

/**
 * User profile data model.
 */
class MethodAnnotationDocblockProfile
{
    use DataModel;

    public string $username;
}

?>
-----
<?php

use Zerotoprod\DataModel\DataModel;

/**
 * User profile data model.
 * @method static self from(array{username: string} $data)
 */
class MethodAnnotationDocblockProfile
{
    use DataModel;

    public string $username;
}

?>
