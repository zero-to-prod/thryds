<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class SkipMagic
{
    public function __construct()
    {
    }

    public function __toString()
    {
        return 'hello';
    }
}

?>
