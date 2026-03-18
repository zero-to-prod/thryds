<?php

use Utils\Rector\Tests\RequireExhaustiveMatchOnEnumRector\TestStatus;

function processStatus(TestStatus $status): string
{
    return match($status) {
        TestStatus::active => 'Active',
        default => 'Other',
    };
}

?>
-----
<?php

use Utils\Rector\Tests\RequireExhaustiveMatchOnEnumRector\TestStatus;

function processStatus(TestStatus $status): string
{
    // TODO: [RequireExhaustiveMatchOnEnumRector] match() on TestStatus must cover all cases explicitly.
    return match($status) {
        TestStatus::active => 'Active',
        default => 'Other',
    };
}

?>
