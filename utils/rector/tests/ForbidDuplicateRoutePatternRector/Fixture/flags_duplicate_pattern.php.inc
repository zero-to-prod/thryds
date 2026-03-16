<?php

readonly class HomeRoute
{
    public const string pattern = '/';
}

readonly class LandingRoute
{
    public const string pattern = '/';
}

?>
-----
<?php

readonly class HomeRoute
{
    public const string pattern = '/';
}

readonly class LandingRoute
{
    // TODO: [ForbidDuplicateRoutePatternRector] Duplicate route pattern '/'. This pattern is already defined in HomeRoute::pattern. Remove or rename this constant.
    public const string pattern = '/';
}

?>
