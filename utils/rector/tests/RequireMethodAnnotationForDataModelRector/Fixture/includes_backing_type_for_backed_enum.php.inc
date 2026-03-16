<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

enum FixtureStatus: string
{
    case active = 'active';
    case inactive = 'inactive';
}

class MethodAnnotationWithBackedEnum
{
    use DataModel;

    #[Describe([Describe::default => FixtureStatus::active])]
    public FixtureStatus $status;

    public string $name;
}

?>
-----
<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

enum FixtureStatus: string
{
    case active = 'active';
    case inactive = 'inactive';
}

/**
 * @method static self from(array{status?: FixtureStatus|string, name: string} $data)
 */
class MethodAnnotationWithBackedEnum
{
    use DataModel;

    #[Describe([Describe::default => FixtureStatus::active])]
    public FixtureStatus $status;

    public string $name;
}

?>