<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class Fluent
{
    public function setName(string $name)
    {
        return $this;
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class Fluent
{
    public function setName(string $name): static
    {
        return $this;
    }
}

?>
