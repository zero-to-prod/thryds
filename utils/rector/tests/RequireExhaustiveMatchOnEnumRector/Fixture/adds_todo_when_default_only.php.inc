<?php

use Utils\Rector\Tests\RequireExhaustiveMatchOnEnumRector\TestStatus;

function processStatus(TestStatus $status): string
{
    return match($status) {
        default => 'unknown',
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
        default => 'unknown',
    };
}

?>
