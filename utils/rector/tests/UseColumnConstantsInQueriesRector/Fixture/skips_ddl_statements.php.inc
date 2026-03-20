<?php

namespace Test;

class SomeMigration
{
    private $Database;

    public function up(): void
    {
        $this->Database->execute(
            "CREATE TABLE IF NOT EXISTS users (
                id CHAR(26) NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NULL,
                PRIMARY KEY (id)
            )"
        );
    }
}

?>
