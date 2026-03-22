<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the template subdirectory for a Blade enum (view or component).
 *
 * The attribute graph surfaces this so agents know where templates live
 * without reading method implementations.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class TemplateDirectory
{
    public function __construct(
        public string $directory,
    ) {}
}
