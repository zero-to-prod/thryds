<?php

class Dispatcher
{
    // Calls RouteRegistrar::register() to bind all routes.
    public function boot(): void {}
}
?>
-----
<?php

class Dispatcher
{
    // TODO: [ForbidHardcodedCodeReferencesInCommentsRector] 'RouteRegistrar::register' is not AST-reachable — use @see/@link or describe behavior without naming implementations. See: utils/rector/docs/ForbidHardcodedCodeReferencesInCommentsRector.md
    // Calls RouteRegistrar::register() to bind all routes.
    public function boot(): void {}
}
?>
