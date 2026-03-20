<?php

namespace Test;

class SomeController
{
    private $Database;

    public function store(): void
    {
        $this->Database->execute(
            'INSERT INTO orders (id, total) VALUES (:id, :total)',
            [':id' => '1', ':total' => 100],
        );
    }
}

?>
