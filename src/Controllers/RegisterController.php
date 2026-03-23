<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use ZeroToProd\Thryds\Attributes\HandlesMethod;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Attributes\Persists;
use ZeroToProd\Thryds\Attributes\RedirectsTo;
use ZeroToProd\Thryds\Attributes\RendersView;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Queries\CreateUserQuery;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Routes\HttpMethod;
use ZeroToProd\Thryds\Routes\RouteList;
use ZeroToProd\Thryds\Tables\User;

#[HandlesRoute(RouteList::register)]
#[RendersView(View::register)]
#[Persists(User::class)]
#[RedirectsTo(RouteList::login)]
readonly class RegisterController
{
    #[HandlesMethod(HttpMethod::POST)]
    public function post(RegisterRequest $RegisterRequest): ResponseInterface
    {
        CreateUserQuery::create($RegisterRequest);

        return new RedirectResponse(RouteList::login->value);
    }
}
