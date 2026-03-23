<?php

class Bootstrapper
{
    // Uses \App\Routes\RouteRegistrar internally.
    public function init(): void {}
}
?>
-----
<?php

class Bootstrapper
{
    // TODO: [ForbidHardcodedCodeReferencesInCommentsRector] '\App\Routes\RouteRegistrar' is not AST-reachable — use @see/@link or describe behavior without naming implementations. See: utils/rector/docs/ForbidHardcodedCodeReferencesInCommentsRector.md
    // Uses \App\Routes\RouteRegistrar internally.
    public function init(): void {}
}
?>
