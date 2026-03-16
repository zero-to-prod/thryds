<?php

use Zerotoprod\DataModel\DataModel;

class UserProfile
{
    use DataModel;

    public const string username = 'username';
    public string $username;

    public string $email;
}

$UserProfile = UserProfile::from([
    'username' => 'john',
    'email' => 'john@example.com',
]);

?>
-----
<?php

use Zerotoprod\DataModel\DataModel;

class UserProfile
{
    use DataModel;

    public const string username = 'username';
    public string $username;
    /** @see $email */
    public const string email = 'email';

    public string $email;
}

$UserProfile = UserProfile::from([
    \UserProfile::username => 'john',
    \UserProfile::email => 'john@example.com',
]);

?>
