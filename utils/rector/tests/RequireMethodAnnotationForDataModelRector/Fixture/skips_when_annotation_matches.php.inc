<?php

use Zerotoprod\DataModel\DataModel;

/**
 * @method static self from(array{username: string} $data)
 */
class MethodAnnotationUser
{
    use DataModel;

    public string $username;
}

?>
