<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares which ViewModels a view receives as template data.
 *
 * Applied to View enum cases. Replaces `use` import scanning as the structural metadata source.
 *
 * @example
 * #[ReceivesViewModel(ErrorViewModel::class)]
 * case error = 'error';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
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
