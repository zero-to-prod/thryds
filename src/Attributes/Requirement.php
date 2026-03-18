<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Links a class or method to one or more requirements in requirements.yaml.
 *
 * Usage: #[Requirement('TRACE-001')] or #[Requirement('AUTH-001', 'SEC-001')]
 *
 * Validated by ValidateRequirementIdsRector — every ID must exist in requirements.yaml.
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
