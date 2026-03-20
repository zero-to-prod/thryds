<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\ExtendsLayout;
use ZeroToProd\Thryds\Attributes\PageTitle;
use ZeroToProd\Thryds\Attributes\ReceivesViewModel;
use ZeroToProd\Thryds\Attributes\UsesComponent;
use ZeroToProd\Thryds\UI\Domain;
use ZeroToProd\Thryds\ViewModels\ErrorMessages;
use ZeroToProd\Thryds\ViewModels\ErrorViewModel;
use ZeroToProd\Thryds\ViewModels\RegisterViewModel;

// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'base' (used 6x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
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
    #[ExtendsLayout('base')]
    #[PageTitle('About — Thryds')]
    #[UsesComponent(Component::card)]
    case about = 'about';

    #[ExtendsLayout('base')]
    #[PageTitle('Error — Thryds')]
    #[UsesComponent(
        Component::alert,
        Component::card
    )]
    #[ReceivesViewModel(ErrorViewModel::class)]
    case error = 'error';

    #[ExtendsLayout('base')]
    #[PageTitle('Thryds')]
    #[UsesComponent(
        Component::card,
        Component::button
    )]
    case home = 'home';

    #[ExtendsLayout('base')]
    #[PageTitle('Login — Thryds')]
    #[UsesComponent(
        Component::card,
        Component::form_group,
        Component::input,
        Component::button
    )]
    case login = 'login';

    #[ExtendsLayout('base')]
    #[PageTitle('Register — Thryds')]
    #[UsesComponent(
        Component::card,
        Component::form_group,
        Component::input,
        Component::button
    )]
    #[ReceivesViewModel(RegisterViewModel::class)]
    case register = 'register';

    #[ExtendsLayout('base')]
    #[PageTitle('Styleguide — Thryds')]
    #[UsesComponent(
        Component::alert,
        Component::button,
        Component::card,
        Component::form_group,
        Component::input
    )]
    case styleguide = 'styleguide';

    /** @return array<string, mixed> Returns stub data for preload rendering. Most views need none; views with ViewModels return their minimum required data. */
    public function stubData(): array
    {
        return match ($this) {
            self::error => [
                ErrorViewModel::view_key => ErrorViewModel::from([
                    ErrorViewModel::message => ErrorMessages::test->value,
                    ErrorViewModel::status_code => 500,
                ]),
            ],
            self::register => [
                RegisterViewModel::view_key => RegisterViewModel::from([
                    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. '' is a value of RegisterViewModel::$name. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
                    RegisterViewModel::name => '',
                    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. '' is a value of RegisterViewModel::$email. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
                    RegisterViewModel::email => '',
                    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. '' is a value of RegisterViewModel::$name_error. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
                    RegisterViewModel::name_error => '',
                    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. '' is a value of RegisterViewModel::$email_error. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
                    RegisterViewModel::email_error => '',
                    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. '' is a value of RegisterViewModel::$password_error. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
                    RegisterViewModel::password_error => '',
                    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. '' is a value of RegisterViewModel::$password_confirmation_error. Replace with enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
                    RegisterViewModel::password_confirmation_error => '',
                ]),
            ],
            default => [],
        };
    }
}
