<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Routes\Route;

final class RegisterRouteTest extends IntegrationTestCase
{
    #[Test]
    public function rendersRegistrationPageAsHtml(): void
    {
        $ResponseInterface = $this->get(Route::register);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::TEXT_HTML, $ResponseInterface->getHeaderLine('Content-Type'));

        $body = (string) $ResponseInterface->getBody();
        $this->assertStringContainsString('Register', haystack: $body);
        $this->assertStringContainsString('</html>', haystack: $body);
    }
}
