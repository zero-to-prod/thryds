<?php

use Utils\Rector\Tests\ValidateChecklistPathsRector\TestSourceOfTruth;

#[TestSourceOfTruth(for: 'example', addCase: '1. Add case. 2. Update scripts/nonexistent.php.')]
enum Example: string
{
    case foo = 'foo';
}

?>
-----
<?php

use Utils\Rector\Tests\ValidateChecklistPathsRector\TestSourceOfTruth;

// TODO: [ValidateChecklistPathsRector] Example references 'scripts/nonexistent.php' in addCase, but this file does not exist. Update the checklist.
#[TestSourceOfTruth(for: 'example', addCase: '1. Add case. 2. Update scripts/nonexistent.php.')]
enum Example: string
{
    case foo = 'foo';
}

?>
