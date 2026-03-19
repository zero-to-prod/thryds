<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\UI;

use ZeroToProd\Thryds\Attributes\ClosedSet;

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
