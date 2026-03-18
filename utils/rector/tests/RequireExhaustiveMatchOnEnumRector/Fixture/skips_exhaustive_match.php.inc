<?php

use Utils\Rector\Tests\RequireExhaustiveMatchOnEnumRector\TestStatus;

function processStatus(TestStatus $status): string
{
    return match($status) {
        TestStatus::active => 'Active',
        TestStatus::inactive => 'Inactive',
        TestStatus::pending => 'Pending',
    };
}

?>
-----
<?php

use Utils\Rector\Tests\RequireExhaustiveMatchOnEnumRector\TestStatus;

function processStatus(TestStatus $status): string
{
    return match($status) {
        TestStatus::active => 'Active',
        TestStatus::inactive => 'Inactive',
        TestStatus::pending => 'Pending',
    };
}

?>
