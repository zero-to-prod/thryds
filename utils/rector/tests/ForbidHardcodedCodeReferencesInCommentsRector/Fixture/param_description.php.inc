<?php

class Service
{
    /** @param string $name Passed to RouteRegistrar::register() downstream. */
    public function run(string $name): void {}
}
?>
-----
<?php

class Service
{
    // TODO: [ForbidHardcodedCodeReferencesInCommentsRector] 'RouteRegistrar::register' is not AST-reachable — use @see/@link or describe behavior without naming implementations. See: utils/rector/docs/ForbidHardcodedCodeReferencesInCommentsRector.md
    /** @param string $name Passed to RouteRegistrar::register() downstream. */
    public function run(string $name): void {}
}
?>
