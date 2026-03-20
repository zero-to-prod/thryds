<?php

namespace Test;

class SomeController
{
    private $Database;

    public function find(): void
    {
        $this->Database->one(
            'SELECT * FROM users WHERE email = :email',
            [':email' => 'test@example.com'],
        );
    }
}

?>
-----
<?php

namespace Test;

class SomeController
{
    private $Database;

    public function find(): void
    {
        $this->Database->one(
            'SELECT * FROM ' . \ZeroToProd\Thryds\Tables\User::tableName() . ' WHERE ' . \ZeroToProd\Thryds\Tables\User::email . ' = :' . \ZeroToProd\Thryds\Tables\User::email,
            [':' . \ZeroToProd\Thryds\Tables\User::email => 'test@example.com'],
        );
    }
}

?>
