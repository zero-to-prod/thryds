<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use ZeroToProd\Framework\Attributes\HandlesMethod;
use ZeroToProd\Framework\Attributes\HandlesRoute;
use ZeroToProd\Framework\Attributes\Persists;
use ZeroToProd\Framework\Attributes\RedirectsTo;
use ZeroToProd\Framework\Attributes\RendersView;
use ZeroToProd\Framework\Routes\HttpMethod;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Queries\CreateUserQuery;
use ZeroToProd\Thryds\Requests\RegisterRequest;
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
