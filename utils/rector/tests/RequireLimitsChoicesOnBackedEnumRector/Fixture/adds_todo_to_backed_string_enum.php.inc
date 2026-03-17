<?php

enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
-----
<?php

// TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum Permission must declare #[LimitsChoices] — enums limit choices (ADR-007).
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
