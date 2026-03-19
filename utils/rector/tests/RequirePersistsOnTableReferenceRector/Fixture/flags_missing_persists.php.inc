<?php

namespace TestControllers;

use TestTables\User;

class RegisterController
{
    public function __invoke(): void {}
}

?>
-----
<?php

namespace TestControllers;

use TestTables\User;

// TODO: [RequirePersistsOnTableReferenceRector] 'RegisterController' imports 'User' from the tables namespace but is missing #[Persists(User::class)].
class RegisterController
{
    public function __invoke(): void {}
}

?>
