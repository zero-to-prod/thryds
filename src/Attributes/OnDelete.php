<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Schema\ReferentialAction;

/**
 * Declares the ON DELETE referential action for a #[ForeignKey] column.
 *
 * Place on the same enum case as #[ForeignKey].
 * Defaults to RESTRICT when omitted.
 *
 * @example
 * #[ForeignKey(BackedEnum: UserTable::id)]
 * #[OnDelete(ReferentialAction: ReferentialAction::CASCADE)]
 * case user_id = 'user_id';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class OnDelete
{
    public function __construct(
        public ReferentialAction $ReferentialAction = ReferentialAction::RESTRICT,
    ) {}
}
