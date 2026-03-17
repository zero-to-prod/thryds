<?php

use Utils\Rector\Tests\RequireLimitsChoicesOnBackedEnumRector\TestLimitsChoices;

#[TestLimitsChoices(domain: 'permissions')]
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireLimitsChoicesOnBackedEnumRector\TestLimitsChoices;

#[TestLimitsChoices(domain: 'permissions')]
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
