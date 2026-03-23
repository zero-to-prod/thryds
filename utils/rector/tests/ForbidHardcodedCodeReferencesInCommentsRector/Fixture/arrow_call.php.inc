<?php

class Handler
{
    // Then ->dispatch() processes the request.
    public function handle(): void {}
}
?>
-----
<?php

class Handler
{
    // TODO: [ForbidHardcodedCodeReferencesInCommentsRector] '->dispatch(' is not AST-reachable — use @see/@link or describe behavior without naming implementations. See: utils/rector/docs/ForbidHardcodedCodeReferencesInCommentsRector.md
    // Then ->dispatch() processes the request.
    public function handle(): void {}
}
?>
