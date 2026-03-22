<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use ReflectionEnum;
use Tempest\Blade\Blade;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\Prop;
use ZeroToProd\Thryds\Attributes\TemplateDirectory;
use ZeroToProd\Thryds\UI\AlertVariant;
use ZeroToProd\Thryds\UI\ButtonSize;
use ZeroToProd\Thryds\UI\ButtonVariant;
use ZeroToProd\Thryds\UI\Domain;
use ZeroToProd\Thryds\UI\InputType;
use ZeroToProd\Thryds\UI\Props;

/**
 * Blade component identifiers. Each case maps to templates/components/{value}.blade.php.
 *
 * Components are registered as <x-{value}> aliases in App::bootBlade().
 */
#[TemplateDirectory('components')]
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
    public const string BUTTON = 'button';

    /** Inline status banner for feedback messages (info, danger, success). */
    #[Prop(
        Props::variant,
        AlertVariant::info
    )]
    case alert = 'alert';

    /** Action trigger with configurable visual variant and size. */
    #[Prop(
        Props::variant,
        ButtonVariant::primary
    )]
    #[Prop(
        Props::size,
        ButtonSize::md
    )]
    #[Prop(
        Props::type,
        self::BUTTON
    )]
    case button = self::BUTTON;

    /** Contained surface for grouping related content. */
    case card = 'card';

    /** Label + input wrapper that enforces consistent form field layout. */
    #[Prop(
        Props::label,
        ''
    )]
    #[Prop(
        Props::error,
        ''
    )]
    case form_group = 'form-group';

    /** Text field bound to a typed HTML input type. */
    #[Prop(
        Props::type,
        InputType::text
    )]
    case input = 'input';

    public static function register(Blade $Blade): void
    {
        foreach (self::cases() as $Component) {
            $Blade->compiler()->component($Component->viewName(), $Component->value);
        }
    }

    /** Resolve the template directory declared by #[TemplateDirectory]. */
    public static function templateDirectory(): string
    {
        return new ReflectionEnum(self::class)
            ->getAttributes(TemplateDirectory::class)[0]
            ->newInstance()
            ->directory;
    }

    /** Blade view name used by compiler()->component(). */
    public function viewName(): string
    {
        return self::templateDirectory() . '.' . $this->value;
    }
}
