<?php

namespace Fixture;

class Guard
{
    public function check(array $users): void
    {
        foreach ($users as $User) {
            if ($User->isActive()) {
                if ($User->hasPermission('edit')) {
                    $this->allow($User);
                }
            }
            $this->log($User);
        }
    }
}

?>
-----
<?php

namespace Fixture;

class Guard
{
    public function check(array $users): void
    {
        foreach ($users as $User) {
            if ($User->isActive() && $User->hasPermission('edit')) {
                $this->allow($User);
            }
            $this->log($User);
        }
    }
}

?>
