<?php

namespace Fixture;

class Mailer
{
    public function send(string $to, string $subject, string $body, bool $html = false): void
    {
    }
}

$mailer = new Mailer();

// Relies on $html default — will have arg added with named syntax.
$mailer->send('a@b.com', 'Hi', 'Body');
// Already explicit — untouched.
$mailer->send('a@b.com', 'Hi', 'Body', true);
?>
-----
<?php

namespace Fixture;

class Mailer
{
    public function send(string $to, string $subject, string $body, bool $html): void
    {
    }
}

$mailer = new Mailer();

// Relies on $html default — will have arg added with named syntax.
$mailer->send('a@b.com', 'Hi', 'Body', html: false);
// Already explicit — untouched.
$mailer->send('a@b.com', 'Hi', 'Body', true);
?>
