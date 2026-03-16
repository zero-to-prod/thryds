<?php

readonly class PostRoute
{
    public const string pattern = '/posts/{post}/comments/{comment}';
}

?>
-----
<?php

readonly class PostRoute
{
    public const string pattern = '/posts/{post}/comments/{comment}';
    public const string post = 'post';
    public const string comment = 'comment';
}

?>
