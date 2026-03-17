<?php

enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
-----
<?php

// TODO: [RequireClosedSetOnBackedEnumRector] Backed enum Permission must declare #[ClosedSet] — enums limit choices (ADR-007).
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>