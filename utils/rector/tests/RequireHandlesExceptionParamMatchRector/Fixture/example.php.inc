<?php

namespace Utils\Rector\Tests\RequireHandlesExceptionParamMatchRector\Fixture;

use ZeroToProd\Thryds\Attributes\HandlesException;

class MismatchHandler
{
    #[HandlesException(\Throwable::class)]
    public function handleHttp(\League\Route\Http\Exception $Exception): void {}
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireHandlesExceptionParamMatchRector\Fixture;

use ZeroToProd\Thryds\Attributes\HandlesException;

class MismatchHandler
{
    #[HandlesException(\League\Route\Http\Exception::class)]
    public function handleHttp(\League\Route\Http\Exception $Exception): void {}
}

?>
