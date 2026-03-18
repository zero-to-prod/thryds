<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

#[ClosedSet(Domain::input_types, addCase: '1. Add enum case. 2. Verify the value is a valid HTML input type.')]
enum InputType: string
{
    case text = 'text';
    case email = 'email';
    case password = 'password';
}
