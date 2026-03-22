<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use ReflectionClass;
use ReflectionEnumUnitCase;
use ReflectionException;
use ReflectionProperty;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\ExtendsLayout;
use ZeroToProd\Thryds\Attributes\PageTitle;
use ZeroToProd\Thryds\Attributes\ReceivesViewModel;
use ZeroToProd\Thryds\Attributes\StubValue;
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
        3. Implement template content. Apply #[StubValue] to ViewModel properties if used.
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
     * @param class-string $class
     * @return list<ReflectionProperty>
     */
    private static function viewModelProperties(string $class): array
    {
        /** @var array<class-string, list<ReflectionProperty>> */
        static $cache = [];

        return $cache[$class] ??= new ReflectionClass(objectOrClass: $class)->getProperties();
    }

    /** @return class-string[] ViewModel classes declared via #[ReceivesViewModel]. */
    public function viewModels(): array
    {
        /** @var array<string, class-string[]> */
        static $cache = [];

        if (!array_key_exists($this->name, array: $cache)) {
            $attrs = new ReflectionEnumUnitCase(self::class, $this->name)
                ->getAttributes(ReceivesViewModel::class);
            $cache[$this->name] = $attrs !== [] ? $attrs[0]->newInstance()->view_models : [];
        }

        return $cache[$this->name];
    }

    /**
     * @return array<string, mixed> Returns stub data for preload rendering. Most views need none; views with ViewModels return their minimum required data.
     * @throws ReflectionException
     */
    public function stubData(): array
    {
        /** @var array<string, array<string, mixed>> */
        static $cache = [];

        if (isset($cache[$this->name])) {
            return $cache[$this->name];
        }

        $view_models = $this->viewModels();

        if ($view_models === []) {
            return $cache[$this->name] = [];
        }

        $data = [];
        foreach ($view_models as $vmClass) {
            $defaults = [];
            foreach (self::viewModelProperties(class: $vmClass) as $prop) {
                $attrs = $prop->getAttributes(StubValue::class);
                if ($attrs !== []) {
                    $defaults[$prop->getName()] = $attrs[0]->newInstance()->value;
                }
            }
            $data[$vmClass::view_key] = $vmClass::from($defaults);
        }

        return $cache[$this->name] = $data;
    }
}
