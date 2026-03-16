<?php

namespace Fixture;

class Checker
{
    public function validate(int $count): void
    {
        if ($count > 0) {
            if ($count <= 100) {
                if ($this->isReady()) {
                    $this->process();
                    $this->finish();
                }
            }
        }
    }
}

?>
-----
<?php

namespace Fixture;

class Checker
{
    public function validate(int $count): void
    {
        if ($count <= 0) {
            return;
        }
        if ($count > 100) {
            return;
        }
        if (!$this->isReady()) {
            return;
        }
        $this->process();
        $this->finish();
    }
}

?>
