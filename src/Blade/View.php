<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use ReflectionClass;
use ReflectionEnumUnitCase;
use ReflectionException;
use ReflectionNamedType;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\ExtendsLayout;
use ZeroToProd\Thryds\Attributes\PageTitle;
use ZeroToProd\Thryds\Attributes\ReceivesViewModel;
use ZeroToProd\Thryds\Attributes\UsesComponent;
use ZeroToProd\Thryds\UI\Domain;
use ZeroToProd\Thryds\UI\Layout;
use ZeroToProd\Thryds\ViewModels\ErrorViewModel;
use ZeroToProd\Thryds\ViewModels\RegisterViewModel;

/**
 * Blade template identifiers. Each case maps to templates/{value}.blade.php.
 *
 * Pass the value to Blade::make(view: View::name->value).
 * For views with view-model data, pass the ViewModel via view_key as the array key.
 */
#[ClosedSet(
    Domain::blade_templates,
    addCase: <<<TEXT
        1. Add entry to thryds.yaml views section.
        2. Run ./run sync:manifest.
        3. Implement template content and stubData() if ViewModel is used.
        4. Run ./run fix:all.
    TEXT,
)]
enum View: string
{
    #[ExtendsLayout(Layout::base)]
    #[PageTitle('About — Thryds')]
    #[UsesComponent(Component::card)]
    case about = 'about';

    #[ExtendsLayout(Layout::base)]
    #[PageTitle('Error — Thryds')]
    #[UsesComponent(
        Component::alert,
        Component::card
    )]
    #[ReceivesViewModel(ErrorViewModel::class)]
    case error = 'error';

    #[ExtendsLayout(Layout::base)]
    #[PageTitle('Thryds')]
    #[UsesComponent(
        Component::card,
        Component::button
    )]
    case home = 'home';

    #[ExtendsLayout(Layout::base)]
    #[PageTitle('Login — Thryds')]
    #[UsesComponent(
        Component::card,
        Component::form_group,
        Component::input,
        Component::button
    )]
    case login = 'login';

    #[ExtendsLayout(Layout::base)]
    #[PageTitle('Register — Thryds')]
    #[UsesComponent(
        Component::card,
        Component::form_group,
        Component::input,
        Component::button
    )]
    #[ReceivesViewModel(RegisterViewModel::class)]
    case register = 'register';

    #[ExtendsLayout(Layout::base)]
    #[PageTitle('Styleguide — Thryds')]
    #[UsesComponent(
        Component::alert,
        Component::button,
        Component::card,
        Component::form_group,
        Component::input
    )]
    case styleguide = 'styleguide';

    /** Reads the title declared via {@see PageTitle} on this case. */
    public function pageTitle(): string
    {
        return new ReflectionEnumUnitCase(self::class, $this->name)
            ->getAttributes(PageTitle::class)[0]
            ->newInstance()
            ->title;
    }

    /**
     * @return array<string, mixed> Returns stub data for preload rendering. Most views need none; views with ViewModels return their minimum required data.
     * @throws ReflectionException
     */
    public function stubData(): array
    {
        $attrs = new ReflectionEnumUnitCase(self::class, $this->name)
            ->getAttributes(ReceivesViewModel::class);

        if ($attrs === []) {
            return [];
        }

        $data = [];
        // TODO: Reflection on static class structure should be resolved at construction, not per-invocation. See: utils/rector/docs/ForbidReflectionInInstanceMethodRector.md
        foreach ($attrs[0]->newInstance()->view_models as $vmClass) {
            $defaults = [];
            foreach (new ReflectionClass(objectOrClass: $vmClass)->getProperties() as $prop) {
                $type = $prop->getType();
                $defaults[$prop->getName()] = ($type instanceof ReflectionNamedType
                    ? ScalarDefault::tryFrom($type->getName())
                    : null)?->zeroValue();
            }
            $data[$vmClass::view_key] = $vmClass::from($defaults);
        }

        return $data;
    }
}
