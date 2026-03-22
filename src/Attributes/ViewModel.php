<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares a class as a Blade view model with a named template variable key.
 *
 * The key parameter makes the Blade variable name visible in the attribute graph,
 * so agents can discover view–data bindings without reading runtime code.
 *
 * Effects enforced by Rector ({@see \Utils\Rector\Rector\AddViewKeyConstantRector}):
 *   - Adds `public const string view_key = 'ShortClassName';`
 *   - Sets the key parameter on this attribute to match
 *
 * The view_key constant is used as the Blade template variable key:
 *   $Blade->make(view: View::error, data: [ErrorViewModel::view_key => $vm])
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class ViewModel
{
    /**
     * Checklist for adding a new ViewModel — read by the inventory script to generate extension_guides.
     */
    public const string addCase
        = "1. Add entry to thryds.yaml viewmodels section.\n"
        . "2. Run ./run sync:manifest.\n"
        . "3. Apply #[StubValue(...)] to each property for preload rendering.\n"
        . '4. Run ./run fix:all.';

    /**
     * @param string $key Blade template variable name (short class name). Auto-populated by Rector.
     */
    public function __construct(
        public string $key,
    ) {}
}
