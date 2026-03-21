<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ZeroToProd\Thryds\Attributes\Persists;
use ZeroToProd\Thryds\Attributes\RedirectsTo;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Queries\CreateUserQuery;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Routes\HttpMethod;
use ZeroToProd\Thryds\Routes\Route;
use ZeroToProd\Thryds\Tables\User;
use ZeroToProd\Thryds\Validation\Validator;
use ZeroToProd\Thryds\ViewModels\RegisterViewModel;

// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string ', :' (used 4x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
#[Persists(User::class)]
#[RedirectsTo(Route::login)]
readonly class RegisterController
{
    public function __invoke(ServerRequestInterface $ServerRequestInterface): ResponseInterface
    {
        if ($ServerRequestInterface->getMethod() === HttpMethod::POST->value) {
            $RegisterRequest = RegisterRequest::from($ServerRequestInterface->getParsedBody());

            $errors = Validator::validate(model: $RegisterRequest);
            if ($errors !== []) {
                return new HtmlResponse(
                    html: blade()->make(view: View::register->value, data: [
                        RegisterViewModel::view_key => RegisterViewModel::from([
                            ...$RegisterRequest->toArray(),
                            ...$errors,
                        ]),
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

        return new HtmlResponse(
            html: blade()->make(
                view: View::register->value,
                data: [RegisterViewModel::view_key => RegisterViewModel::from('')]
            )->render()
        );
    }
}
