<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\Prop;
use ZeroToProd\Thryds\UI\AlertVariant;
use ZeroToProd\Thryds\UI\ButtonSize;
use ZeroToProd\Thryds\UI\ButtonVariant;
use ZeroToProd\Thryds\UI\Domain;
use ZeroToProd\Thryds\UI\InputType;

// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'button' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'type' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'variant' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
/**
 * Blade component identifiers. Each case maps to templates/components/{value}.blade.php.
 *
 * Components are registered as <x-{value}> aliases in App::bootBlade().
 */
#[ClosedSet(
    Domain::blade_components,
    addCase: <<<TEXT
        1. Add entry to thryds.yaml components section.
        2. Run ./run sync:manifest.
        3. Implement component template and add example to styleguide.
        4. Run ./run fix:all.
    TEXT,
)]
enum Component: string
{
    /** Inline status banner for feedback messages (info, danger, success). */
    #[Prop(
        'variant',
        default: 'info',
        enum: AlertVariant::class
    )]
    case alert = 'alert';

    /** Action trigger with configurable visual variant and size. */
    #[Prop(
        'variant',
        default: 'primary',
        enum: ButtonVariant::class
    )]
    #[Prop(
        'size',
        default: 'md',
        enum: ButtonSize::class
    )]
    #[Prop(
        'type',
        default: 'button',
        enum: null
    )]
    case button = 'button';

    /** Contained surface for grouping related content. */
    case card = 'card';

    /** Label + input wrapper that enforces consistent form field layout. */
    #[Prop(
        'label',
        default: '',
        enum: null
    )]
    #[Prop(
        'error',
        default: '',
        enum: null
    )]
    case form_group = 'form-group';

    /** Text field bound to a typed HTML input type. */
    #[Prop(
        'type',
        default: 'text',
        enum: InputType::class
    )]
    case input = 'input';

    /** Blade view name used by compiler()->component(). */
    public function viewName(): string
    {
        return 'components.' . $this->value;
    }
}
