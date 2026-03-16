<?php

class EmptyService
{
    public function handle(): void
    {
        echo 'handled';
    }
}

?>
-----
<?php

readonly class EmptyService
{
    public function handle(): void
    {
        echo 'handled';
    }
}

?>
