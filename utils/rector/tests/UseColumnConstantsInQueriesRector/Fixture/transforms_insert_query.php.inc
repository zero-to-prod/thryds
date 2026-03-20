<?php

namespace Test;

class SomeController
{
    private $Database;

    public function store(): void
    {
        $id = '123';
        $name = 'test';
        $email = 'test@example.com';
        $password = 'hashed';

        $this->Database->execute(
            'INSERT INTO users (id, name, email, password) VALUES (:id, :name, :email, :password)',
            [
                ':id' => $id,
                ':name' => $name,
                ':email' => $email,
                ':password' => $password,
            ],
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

    public function store(): void
    {
        $id = '123';
        $name = 'test';
        $email = 'test@example.com';
        $password = 'hashed';

        $this->Database->execute(
            'INSERT INTO ' . \ZeroToProd\Thryds\Tables\User::tableName() . ' (' . \ZeroToProd\Thryds\Tables\User::id . ', ' . \ZeroToProd\Thryds\Tables\User::name . ', ' . \ZeroToProd\Thryds\Tables\User::email . ', ' . \ZeroToProd\Thryds\Tables\User::password . ') VALUES (:' . \ZeroToProd\Thryds\Tables\User::id . ', :' . \ZeroToProd\Thryds\Tables\User::name . ', :' . \ZeroToProd\Thryds\Tables\User::email . ', :' . \ZeroToProd\Thryds\Tables\User::password . ')',
            [
                ':' . \ZeroToProd\Thryds\Tables\User::id => $id,
                ':' . \ZeroToProd\Thryds\Tables\User::name => $name,
                ':' . \ZeroToProd\Thryds\Tables\User::email => $email,
                ':' . \ZeroToProd\Thryds\Tables\User::password => $password,
            ],
        );
    }
}

?>
