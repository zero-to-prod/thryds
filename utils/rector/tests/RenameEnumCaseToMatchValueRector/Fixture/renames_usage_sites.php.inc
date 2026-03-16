<?php

namespace Utils\Rector\Tests\RenameEnumCaseToMatchValueRector\Fixture;

enum Env: string
{
    case Production = 'production';
    case Development = 'development';
}

function example(): void
{
    $env = Env::Production;
    $val = Env::Development->value;
}

?>
-----
<?php

namespace Utils\Rector\Tests\RenameEnumCaseToMatchValueRector\Fixture;

enum Env: string
{
    case production = 'production';
    case development = 'development';
}

function example(): void
{
    $env = Env::production;
    $val = Env::development->value;
}

?>
