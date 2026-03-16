<?php

namespace Fixture;

class Service
{
    public function process(Order $Order): void
    {
        if ($Order->isValid()) {
            if ($Order->isPaid()) {
                if ($Order->hasItems()) {
                    $this->validate($Order);
                    $this->save($Order);
                    $this->notify($Order);
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
    public function process(Order $Order): void
    {
        if (!$Order->isValid()) {
            return;
        }
        if (!$Order->isPaid()) {
            return;
        }
        if (!$Order->hasItems()) {
            return;
        }
        $this->validate($Order);
        $this->save($Order);
        $this->notify($Order);
    }
}

?>
