<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Marker attribute: signals that this class is a Blade view model.
 *
 * Effects enforced by Rector ({@see \Utils\Rector\Rector\AddViewKeyConstantRector}):
 *   - Adds `public const string view_key = 'ShortClassName';`
 *
 * The view_key constant is used as the Blade template variable key:
 *   $Blade->make(view: View::error, data: [ErrorViewModel::view_key => $vm])
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ViewModel {}
