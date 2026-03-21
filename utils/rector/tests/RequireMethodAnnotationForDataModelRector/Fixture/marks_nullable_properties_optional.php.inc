<?php

use Zerotoprod\DataModel\DataModel;

class NullablePropertiesModel
{
    use DataModel;

    public string $name;
    public ?string $nickname;
    public int $age;
    public ?int $score;
}

?>
-----
<?php

use Zerotoprod\DataModel\DataModel;

/**
 * @method static self from(array{name: string, nickname?: ?string, age: int, score?: ?int} $data)
 */
class NullablePropertiesModel
{
    use DataModel;

    public string $name;
    public ?string $nickname;
    public int $age;
    public ?int $score;
}

?>
