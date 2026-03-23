<?php

class Router
{
    /** Dispatch to the handler declared on the #[RouteOperation]. */
    public function dispatch(): void {}
}
?>
-----
<?php

class Router
{
    // TODO: [ForbidHardcodedCodeReferencesInCommentsRector] '#[RouteOperation]' is not AST-reachable — use @see/@link or describe behavior without naming implementations. See: utils/rector/docs/ForbidHardcodedCodeReferencesInCommentsRector.md
    /** Dispatch to the handler declared on the #[RouteOperation]. */
    public function dispatch(): void {}
}
?>
