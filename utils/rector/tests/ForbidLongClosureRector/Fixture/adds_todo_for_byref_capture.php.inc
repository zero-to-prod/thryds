<?php

namespace Fixture;

class Processor
{
    public function run(): void
    {
        $transform = function (array $items) use (&$count): void {
            foreach ($items as $item) {
                echo $item;
            }
            $count++;
            log_it();
            finish();
        };
    }
}

?>
-----
<?php

namespace Fixture;

class Processor
{
    public function run(): void
    {
        // TODO: Extract closure to a named function or method (captures mutable references)
        $transform = function (array $items) use (&$count): void {
            foreach ($items as $item) {
                echo $item;
            }
            $count++;
            log_it();
            finish();
        };
    }
}

?>
