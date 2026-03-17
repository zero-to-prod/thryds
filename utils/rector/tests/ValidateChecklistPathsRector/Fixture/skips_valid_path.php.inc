<?php

use Utils\Rector\Tests\ValidateChecklistPathsRector\TestSourceOfTruth;

#[TestSourceOfTruth(for: 'example', addCase: '1. Add case. 2. Update Support/existing-file.php.')]
enum Example: string
{
    case foo = 'foo';
}

?>
-----
<?php

use Utils\Rector\Tests\ValidateChecklistPathsRector\TestSourceOfTruth;

#[TestSourceOfTruth(for: 'example', addCase: '1. Add case. 2. Update Support/existing-file.php.')]
enum Example: string
{
    case foo = 'foo';
}

?>
