<?php

namespace Fixture;

class Service
{
    public function handle(): void
    {
        if ($a) {
            if (!$b) {
                if ($c === $d) {
                    if (!$e) {
                        $this->process();
                    }
                }
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
        if ($b) {
            return;
        }
        if ($c !== $d) {
            return;
        }
        if ($e) {
            return;
        }
        $this->process();
    }
}

?>
