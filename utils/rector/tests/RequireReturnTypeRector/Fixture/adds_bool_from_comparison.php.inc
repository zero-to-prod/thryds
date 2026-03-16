<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class AddBoolFromComparison
{
    public string $status;

    public function isActive()
    {
        return $this->status === 'active';
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class AddBoolFromComparison
{
    public string $status;

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

?>
