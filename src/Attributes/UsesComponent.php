<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Blade\Component;

/**
 * Declares which Blade components a view uses.
 *
 * Applied to View enum cases. Replaces <x-*> tag scanning as the structural metadata source.
 *
 * @example
 * #[UsesComponent(Component::card, Component::button)]
 * case home = 'home';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class UsesComponent
{
    /** @var Component[] */
    public array $components;

    public function __construct(Component ...$components)
    {
        $this->components = $components;
    }
}
