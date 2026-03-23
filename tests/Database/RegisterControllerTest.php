<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Database;

use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Framework\Database;
use ZeroToProd\Thryds\Controllers\RegisterController;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Routes\RouteList;
use ZeroToProd\Thryds\Tables\User;

final class RegisterControllerTest extends DatabaseTestCase
{
    private const string TEST_EMAIL = 'test@example.com';
    private const string TEST_PASSWORD = 'password123';

    protected function setUp(): void
    {
        parent::setUp();
        Container::getInstance()->instance(Database::class, $this->Database);
    }

    #[Test]
    public function postCreatesUserAndRedirectsToLogin(): void
    {
        $ResponseInterface = new RegisterController()->post(RegisterRequest::from([
            RegisterRequest::name => 'Test User',
            RegisterRequest::handle => 'testuser',
            RegisterRequest::email => self::TEST_EMAIL,
            RegisterRequest::password => self::TEST_PASSWORD,
            RegisterRequest::password_confirmation => self::TEST_PASSWORD,
        ]));

        $this->assertSame(302, $ResponseInterface->getStatusCode());
        $this->assertSame(RouteList::login->value, $ResponseInterface->getHeaderLine('Location'));
        $this->assertSame(1, (int) $this->Database->scalar(
            'SELECT COUNT(*) FROM ' . User::tableName() . ' WHERE ' . User::email . ' = ?',
            [self::TEST_EMAIL],
        ));
    }
}
