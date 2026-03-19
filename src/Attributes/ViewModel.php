<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

// TODO: [RequireNamesKeysOnConstantsClassRector] ViewModel contains only string constants — add #[NamesKeys] to declare what they name (ADR-007). See: utils/rector/docs/RequireNamesKeysOnConstantsClassRector.md
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
readonly class ViewModel
{
    /**
     * Checklist for adding a new ViewModel — read by the inventory script to generate extension_guides.
     */
    public const string addCase
        = "1. Create src/ViewModels/<ClassName>ViewModel.php:\n"
        . "   - Mark readonly class with #[ViewModel] and use DataModel.\n"
        . "   - Rector will add `public const string view_key = '<ClassName>ViewModel';` automatically.\n"
        . "   - For each field: add a typed public property and a matching string constant with the same name.\n"
        . "2. In src/Blade/View.php, add a case to stubData() returning representative sample data.\n"
        . "3. Use the ViewModel in a template via @php + use statement — inventory emits a receives edge automatically.\n"
        . '4. Pass the ViewModel from the controller: <ClassName>ViewModel::from([...]).';
}
