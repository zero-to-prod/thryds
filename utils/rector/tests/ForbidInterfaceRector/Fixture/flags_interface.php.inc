<?php

namespace Test\ForbidInterfaceRector;

interface Loggable
{
    public function toLogContext(): array;
}

?>
-----
<?php

namespace Test\ForbidInterfaceRector;

// TODO: [ForbidInterfaceRector] Interfaces define implicit contracts — use PHP attributes to declare properties explicitly. Attributes are discoverable, enforceable, and composable without coupling. See: utils/rector/docs/ForbidInterfaceRector.md
interface Loggable
{
    public function toLogContext(): array;
}

?>
