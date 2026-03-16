<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

class Service {
    private string $name;

    public function setName($name): void {
        $this->name = $name;
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireParamTypeRector\Fixture;

class Service {
    private string $name;

    public function setName(string $name): void {
        $this->name = $name;
    }
}

?>
