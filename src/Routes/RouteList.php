<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Routes\Actions\Form;
use ZeroToProd\Framework\Routes\Actions\StaticView;
use ZeroToProd\Framework\Routes\Actions\Validated;
use ZeroToProd\Framework\Routes\HttpMethod;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Controllers\RegisterController;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\UI\Domain;
use ZeroToProd\Thryds\ViewModels\RegisterViewModel;

#[ClosedSet(
    Domain::url_routes,
    addCase: <<<TEXT
    1. Add entry to thryds.yaml routes section.
    2. Run ./run sync:manifest.
    3. Implement controller logic (if controller route).
    4. Run ./run fix:all.
    TEXT
)]
enum RouteList: string
{
    #[Route(
        HttpMethod::GET,
        new StaticView(View::home),
        'Marketing home page'
    )]
    case home = '/';

    #[Route(
        HttpMethod::GET,
        new StaticView(View::about),
        'Company and product information'
    )]
    case about = '/about';

    #[Route(
        HttpMethod::GET,
        new StaticView(View::login),
        'User authentication form'
    )]
    case login = '/login';

    #[Route(
        HttpMethod::GET,
        new Form(
            View::register,
            controller: RegisterController::class,
            request: RegisterRequest::class,
            view_model: RegisterViewModel::class,
        ),
        'New user registration form',
    )]
    #[Route(
        HttpMethod::POST,
        new Validated(
            controller: RegisterController::class,
            request: RegisterRequest::class,
            view_model: RegisterViewModel::class,
        ),
        null,
    )]
    case register = '/register';
}
