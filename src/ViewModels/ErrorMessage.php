<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\ViewModels;

use ZeroToProd\Thryds\Helpers\ClosedSet;
use ZeroToProd\Thryds\Helpers\Domain;

#[ClosedSet(Domain::error_messages, addCase: 'Add enum case. Use the case value wherever ErrorViewModel::$message is set.')]
enum ErrorMessage: string
{
    case test = 'test';
}
