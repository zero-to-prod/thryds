<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\UI;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

/** Allowed HTML input type values for the input component. */
#[ClosedSet(
    Domain::input_types,
    addCase: <<<TEXT
    1. Add enum case.
    2. Verify the value is a valid HTML input type.
    TEXT
)]
enum InputType: string
{
    case text = 'text';
    case email = 'email';
    case password = 'password';
}
