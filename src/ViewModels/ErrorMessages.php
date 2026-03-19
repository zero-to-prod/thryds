<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::error_messages,
    addCase: 'Add enum case. Use the case value wherever ErrorViewModel::$message is set.'
)]
enum ErrorMessages: string
{
    case test = 'test';
}
