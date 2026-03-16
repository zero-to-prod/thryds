<?php

namespace Fixture;

class Handler
{
    public function handle(): void
    {
        try {
            if ($a) {
                if ($b) {
                    if ($c) {
                        $this->doSomething();
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->log($e);
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
        try {
            if (!$a) {
                return;
            }
            if (!$b) {
                return;
            }
            if (!$c) {
                return;
            }
            $this->doSomething();
        } catch (\Throwable $e) {
            $this->log($e);
        }
    }
}

?>
