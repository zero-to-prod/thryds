<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Random\RandomException;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Attributes\Persists;
use ZeroToProd\Thryds\Attributes\RedirectsTo;
use ZeroToProd\Thryds\Attributes\RendersView;
use ZeroToProd\Thryds\Attributes\ValidatesRequest;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Queries\CreateUserQuery;
use ZeroToProd\Thryds\Requests\InputField;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Routes\Route;
use ZeroToProd\Thryds\Tables\User;
use ZeroToProd\Thryds\ViewModels\RegisterViewModel;

#[HandlesRoute(Route::register)]
#[ValidatesRequest(RegisterRequest::class)]
#[RendersView(View::register)]
#[Persists(User::class)]
#[RedirectsTo(Route::login)]
readonly class RegisterController
{
    public function get(): HtmlResponse
    {
        return new HtmlResponse(
            html: blade()->make(
                view: View::register->value,
                data: [
                    RegisterViewModel::view_key => RegisterViewModel::from([]),
                    InputField::fields => InputField::reflect(RegisterRequest::class),
                ]
            )->render()
        );
    }

    /**
     * @throws RandomException
     */
    public function post(RegisterRequest $RegisterRequest): ResponseInterface
    {
        CreateUserQuery::create($RegisterRequest);

        return new RedirectResponse(Route::login->value);
    }
}
