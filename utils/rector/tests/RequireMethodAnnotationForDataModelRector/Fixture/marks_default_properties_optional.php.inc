<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class MethodAnnotationConfig
{
    use DataModel;

    #[Describe([Describe::default => 'production'])]
    public string $app_env;

    public string $template_dir;
}

?>
-----
<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

/**
 * @method static self from(array{app_env?: string, template_dir: string} $data)
 */
class MethodAnnotationConfig
{
    use DataModel;

    #[Describe([Describe::default => 'production'])]
    public string $app_env;

    public string $template_dir;
}

?>
