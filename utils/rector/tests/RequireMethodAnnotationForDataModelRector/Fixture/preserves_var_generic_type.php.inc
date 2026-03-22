<?php

use Zerotoprod\DataModel\DataModel;
use ZeroToProd\Thryds\Attributes\Describe;

/**
 * @method static self from(array{errors?: ?array} $data)
 */
class GenericArrayViewModel
{
    use DataModel;

    /** @var ?array<string, string> */
    #[Describe([
        Describe::nullable => true,
    ])]
    public ?array $errors;
}

?>
-----
<?php

use Zerotoprod\DataModel\DataModel;
use ZeroToProd\Thryds\Attributes\Describe;

/**
 * @method static self from(array{errors?: ?array<string, string>} $data)
 */
class GenericArrayViewModel
{
    use DataModel;

    /** @var ?array<string, string> */
    #[Describe([
        Describe::nullable => true,
    ])]
    public ?array $errors;
}

?>
