<?php

namespace Fixture;

class Handler
{
    public function handle(): void
    {
        if ($a) {
            if ($b) {
                if ($c) {
                    $this->doA();
                } else {
                    $this->doB();
                }
            }
        }
    }
}

?>
-----
<?php

namespace Fixture;

class Handler
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
            $this->doA();
        } else {
            $this->doB();
        }
    }
}

?>
