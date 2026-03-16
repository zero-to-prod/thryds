<?php

namespace Fixture;

class Processor
{
    public function run(): void
    {
        $aggregate = function (array $items) use ($total): void {
            foreach ($items as $item) {
                process($item);
            }
            $total = count($items);
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
        // TODO: Extract closure to a named function or method (modifies captured variables)
        $aggregate = function (array $items) use ($total): void {
            foreach ($items as $item) {
                process($item);
            }
            $total = count($items);
            log_it();
            finish();
        };
    }
}

?>
