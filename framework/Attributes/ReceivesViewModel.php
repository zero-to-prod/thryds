<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Declares which ViewModels a view receives as template data.
 *
 * Applied to View enum cases. Replaces `use` import scanning as the structural metadata source.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
#[HopWeight(0)]
readonly class ReceivesViewModel
{
    /** @var class-string[] */
    public array $view_models;

    /** @param class-string ...$viewModels */
    public function __construct(string ...$viewModels)
    {
        $this->view_models = $viewModels;
    }
}
