<?php

readonly class PostRoute
{
    public const string post = 'post';
}

?>
-----
<?php

// TODO: [RequireRoutePatternConstRector] Route class 'PostRoute' is missing a 'pattern' constant. Define: public const string pattern = '/...';
readonly class PostRoute
{
    public const string post = 'post';
}

?>
