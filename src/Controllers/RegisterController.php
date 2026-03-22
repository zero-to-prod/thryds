<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Random\RandomException;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Attributes\Persists;
use ZeroToProd\Thryds\Attributes\RedirectsTo;
use ZeroToProd\Thryds\Attributes\RendersView;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Queries\CreateUserQuery;
use ZeroToProd\Thryds\Requests\InputField;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Routes\Route;
use ZeroToProd\Thryds\Tables\User;
use ZeroToProd\Thryds\Validation\Validator;
use ZeroToProd\Thryds\ViewModels\RegisterViewModel;

#[HandlesRoute(Route::register)]
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
    public function post(ServerRequestInterface $ServerRequestInterface): ResponseInterface
    {
        /** @phpstan-ignore argument.type (PSR-7 parsed body is always array for form POST) */
        $RegisterRequest = RegisterRequest::from($ServerRequestInterface->getParsedBody());

        $errors = Validator::validate(model: $RegisterRequest);
        if ($errors !== []) {
            return new HtmlResponse(
                html: blade()->make(view: View::register->value, data: [
                    /** @phpstan-ignore argument.type (spread merges request fields with errors key into the expected shape) */
                    RegisterViewModel::view_key => RegisterViewModel::from([...$RegisterRequest->toArray(), RegisterViewModel::errors => $errors]),
                    InputField::fields => InputField::reflect(RegisterRequest::class),
                ])->render()
            );
        }

        CreateUserQuery::create(
            $RegisterRequest->name,
            $RegisterRequest->handle,
            $RegisterRequest->email,
            $RegisterRequest->password
        );

        return new RedirectResponse(Route::login->value);
    }
}
