<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use ZeroToProd\Thryds\ViewModels\ErrorViewModel;

/**
 * Blade template identifiers. Each case maps to templates/{value}.blade.php.
 *
 * Pass the value to Blade::make(view: View::name->value).
 * For views with view-model data, pass the ViewModel via view_key as the array key.
 */
#[ClosedSet(
    Domain::blade_templates,
    addCase: '1. Add enum case. 2. Create templates/{case}.blade.php. 3. If the view requires a ViewModel, add stub data to stubData().',
)]
enum View: string
{
    case about = 'about';
    case error = 'error';
    case home = 'home';
    case login = 'login';
    case register = 'register';
    case styleguide = 'styleguide';

    /** Returns stub data for preload rendering. Most views need none; views with ViewModels return their minimum required data. */
    public function stubData(): array
    {
        return match ($this) {
            self::error => [
                ErrorViewModel::view_key => ErrorViewModel::from([
                    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'test' is a value of ErrorViewModel::$message. Replace with enum case.
                    ErrorViewModel::message => 'test',
                    ErrorViewModel::status_code => 500,
                ]),
            ],
            default => [],
        };
    }
}
