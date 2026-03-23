<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Schema;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::sql_referential_actions,
    addCase: <<<TEXT
    1. Add enum case.
    2. Verify the action is supported by the target database engine.
    TEXT
)]
/**
 * Closed set of referential actions for foreign key constraints.
 *
 * Used in #[ForeignKey] to declare ON DELETE and ON UPDATE behaviour.
 * The backed string value is used directly in the CONSTRAINT clause of DDL.
 */
enum ReferentialAction: string
{
    case CASCADE   = 'CASCADE';
    case SetNull   = 'SET NULL';
    case RESTRICT  = 'RESTRICT';
    case NoAction  = 'NO ACTION';
    case SetDefault = 'SET DEFAULT';
}
