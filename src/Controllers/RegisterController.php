<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Jenssegers\Blade\Blade;
use Laminas\Diactoros\Response\HtmlResponse;
use ZeroToProd\Thryds\Attributes\Persists;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Tables\User;

/** Handles new user registration form submission. */
#[Persists(User::class)]
readonly class RegisterController
{
    public function __construct(private Blade $Blade) {}

    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse(
            html: $this->Blade->make(view: View::register->value)->render(),
        );
    }
}
