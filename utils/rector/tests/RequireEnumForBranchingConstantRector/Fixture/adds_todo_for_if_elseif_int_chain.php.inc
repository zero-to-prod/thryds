<?php

namespace Fixture;

function route(int $code): string
{
    if ($code === 1) {
        return 'home';
    } elseif ($code === 2) {
        return 'about';
    } elseif ($code === 3) {
        return 'contact';
    }
    return 'unknown';
}
?>
-----
<?php

namespace Fixture;

function route(int $code): string
{
    // TODO: [RequireEnumForBranchingConstantRector] Enumerations define sets — $code is compared against 3 literals (1, 2, 3), forming an implicit closed set. Extract to a backed enum with #[ClosedSet] and use match(). See: utils/rector/docs/RequireEnumForBranchingConstantRector.md
    if ($code === 1) {
        return 'home';
    } elseif ($code === 2) {
        return 'about';
    } elseif ($code === 3) {
        return 'contact';
    }
    return 'unknown';
}
?>
