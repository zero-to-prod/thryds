<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Links a class or method to one or more requirements in requirements.yaml.
 *
 * Accepts one or more requirement IDs as arguments.
 *
 * Validated by a Rector rule — every ID must exist in requirements.yaml.
 *
 * @see docs/requirement-tracing.md
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
readonly class Requirement
{
    /** @var string[] */
    public array $ids;

    public function __construct(string ...$ids)
    {
        $this->ids = $ids;
    }
}
