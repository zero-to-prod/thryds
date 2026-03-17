<?php

enum Priority: int
{
    case Low = 1;
    case High = 2;
}

?>
-----
<?php

// TODO: [RequireClosedSetOnBackedEnumRector] Backed enum Priority must declare #[ClosedSet] — enums limit choices (ADR-007).
enum Priority: int
{
    case Low = 1;
    case High = 2;
}

?>