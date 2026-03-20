<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Jenssegers\Blade\Blade;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ZeroToProd\Thryds\Attributes\Persists;
use ZeroToProd\Thryds\Attributes\RedirectsTo;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\Routes\HttpMethod;
use ZeroToProd\Thryds\Routes\Route;
use ZeroToProd\Thryds\Tables\User;
use ZeroToProd\Thryds\ViewModels\RegisterViewModel;

// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string ', :' (used 4x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
#[Persists(User::class)]
#[RedirectsTo(Route::login)]
readonly class RegisterController
{
    public function __construct(
        private Blade $Blade,
        private Database $Database,
    ) {}

    public function __invoke(ServerRequestInterface $ServerRequestInterface): ResponseInterface
    {
        if ($ServerRequestInterface->getMethod() === HttpMethod::POST->value) {
            return $this->handleRegistration($ServerRequestInterface);
        }

        return $this->renderForm();
    }

    private function renderForm(
        string $name = '',
        string $email = '',
        string $name_error = '',
        string $email_error = '',
        string $password_error = '',
        string $password_confirmation_error = '',
    ): HtmlResponse {
        return new HtmlResponse(
            html: $this->Blade->make(view: View::register->value, data: [
                RegisterViewModel::view_key => RegisterViewModel::from([
                    RegisterViewModel::name => $name,
                    RegisterViewModel::email => $email,
                    RegisterViewModel::name_error => $name_error,
                    RegisterViewModel::email_error => $email_error,
                    RegisterViewModel::password_error => $password_error,
                    RegisterViewModel::password_confirmation_error => $password_confirmation_error,
                ]),
            ])->render(),
        );
    }

    private function handleRegistration(ServerRequestInterface $ServerRequestInterface): ResponseInterface
    {
        $body = (array) $ServerRequestInterface->getParsedBody();
        $name = trim((string) ($body[User::name] ?? ''));
        $email = trim((string) ($body[User::email] ?? ''));
        $password = (string) ($body[User::password] ?? '');
        // TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value 'password_confirmation' on the appropriate class. See: utils/rector/docs/ForbidMagicStringArrayKeyRector.md
        $errors = $this->validate($name, $email, $password, (string) ($body['password_confirmation'] ?? ''));
        if ($errors !== []) {
            return $this->renderForm(
                $name,
                $email,
                name_error: $errors[RegisterViewModel::name_error] ?? '',
                email_error: $errors[RegisterViewModel::email_error] ?? '',
                password_error: $errors[RegisterViewModel::password_error] ?? '',
                password_confirmation_error: $errors[RegisterViewModel::password_confirmation_error] ?? '',
            );
        }

        $this->Database->execute(
            'INSERT INTO ' . User::tableName() . ' (' . User::id . ', ' . User::name . ', ' . User::handle . ', ' . User::email . ', ' . User::password . ') VALUES (:' . User::id . ', :' . User::name . ', :' . User::handle . ', :' . User::email . ', :' . User::password . ')',
            [
                ':' . User::id => self::generateId(),
                ':' . User::name => $name,
                ':' . User::handle => self::generateHandle($name),
                ':' . User::email => $email,
                ':' . User::password => password_hash($password, PASSWORD_BCRYPT),
            ],
        );

        return new RedirectResponse(Route::login->value);
    }

    /** @return array<string, string> */
    private function validate(string $name, string $email, string $password, string $passwordConfirmation): array
    {
        $errors = [];

        // TODO: [RequireEnumOrConstInStringComparisonRector] Raw string 'POST' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md
        if ($name === '') {
            $errors[RegisterViewModel::name_error] = 'Name is required.';
        }

        // TODO: [RequireEnumOrConstInStringComparisonRector] Raw string 'POST' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md
        if ($email === '') {
            $errors[RegisterViewModel::email_error] = 'Email is required.';
        } elseif (! filter_var(value: $email, filter: FILTER_VALIDATE_EMAIL)) {
            $errors[RegisterViewModel::email_error] = 'Enter a valid email address.';
        }

        // TODO: [RequireEnumOrConstInStringComparisonRector] Raw string 'POST' in comparison must be backed by an enum or constant. Constants name things, enumerations define sets. See: utils/rector/docs/RequireEnumOrConstInStringComparisonRector.md
        if ($password === '') {
            $errors[RegisterViewModel::password_error] = 'Password is required.';
        } elseif (strlen(string: $password) < 8) {
            $errors[RegisterViewModel::password_error] = 'Password must be at least 8 characters.';
        }

        if ($password !== $passwordConfirmation) {
            $errors[RegisterViewModel::password_confirmation_error] = 'Passwords do not match.';
        }

        return $errors;
    }

    private static function generateId(): string
    {
        return substr(bin2hex(random_bytes(13)), 0, 26);
    }

    private static function generateHandle(string $name): string
    {
        return substr(strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', subject: $name)) . bin2hex(random_bytes(4)), 0, 30);
    }
}
