<?php

namespace Fixture;

class UserService {
    public function getProfile(): array {
        return [
            'name' => 'John',
            'email' => 'john@example.com',
        ];
    }
}

?>
-----
<?php

namespace Fixture;

class UserService {
    public function getProfile(): UserServiceGetProfileResult {
        return new UserServiceGetProfileResult(name: 'John', email: 'john@example.com');
    }
}

?>
