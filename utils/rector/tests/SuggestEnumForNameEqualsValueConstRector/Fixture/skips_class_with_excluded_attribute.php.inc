<?php

namespace Fixture;

use Utils\Rector\Tests\SuggestEnumForNameEqualsValueConstRector\TestClosedSet;

#[TestClosedSet]
readonly class AppEnvConsts
{
    public const string production = 'production';
    public const string staging = 'staging';
    public const string local = 'local';
}

?>
