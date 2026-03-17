<?php

enum Priority: int
{
    case Low = 1;
    case High = 2;
}

?>
-----
<?php

// TODO: [RequireLimitsChoicesOnBackedEnumRector] Backed enum Priority must declare #[LimitsChoices] — enums limit choices (ADR-007).
enum Priority: int
{
    case Low = 1;
    case High = 2;
}

?>
