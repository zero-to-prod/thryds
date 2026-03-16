<?php

namespace Fixture;

class Shipper
{
    public function shipAll(array $orders): void
    {
        foreach ($orders as $order) {
            if ($order->isPaid()) {
                foreach ($order->items as $item) {
                    if ($item->needsShipping()) {
                        $this->ship($item);
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

class Shipper
{
    // TODO: Reduce nesting depth (current: 3, max: 2)
    public function shipAll(array $orders): void
    {
        foreach ($orders as $order) {
            if (!$order->isPaid()) {
                continue;
            }
            foreach ($order->items as $item) {
                if (!$item->needsShipping()) {
                    continue;
                }
                $this->ship($item);
            }
        }
    }
}

?>
