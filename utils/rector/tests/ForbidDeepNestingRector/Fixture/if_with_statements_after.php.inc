<?php

namespace Fixture;

class Service
{
    public function handle(): void
    {
        if ($a) {
            if ($b) {
                if ($c) {
                    $this->doSomething();
                }
                $this->doAfter();
            }
        }
    }
}

?>
-----
<?php

namespace Fixture;

class Service
{
    public function handle(): void
    {
        if (!$a) {
            return;
        }
        if (!$b) {
            return;
        }
        if ($c) {
            $this->doSomething();
        }
        $this->doAfter();
    }
}

?>
