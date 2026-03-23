<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Attributes\CoversRoute;
use ZeroToProd\Thryds\Header;
use ZeroToProd\Thryds\Requests\RegisterRequest;
use ZeroToProd\Thryds\Routes\HttpMethod;
use ZeroToProd\Thryds\Routes\RouteList;

#[CoversRoute(RouteList::register)]
final class RegisterRouteTest extends IntegrationTestCase
{
    private const string REGISTER = 'Register';

    #[Test]
    public function rendersRegistrationPageAsHtml(): void
    {
        $ResponseInterface = $this->get(RouteList::register);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::TEXT_HTML, $ResponseInterface->getHeaderLine(Header::content_type));

        $body = (string) $ResponseInterface->getBody();
        $this->assertStringContainsString(self::REGISTER, haystack: $body);
        $this->assertStringContainsString('</html>', haystack: $body);
    }

    #[Test]
    public function postWithEmptyFieldsReturnsValidationErrors(): void
    {
        $ResponseInterface = $this->App->Router->dispatch(new ServerRequest(
            serverParams: [],
            uploadedFiles: [],
            uri: new Uri(RouteList::register->value),
            method: HttpMethod::POST->value,
        )->withParsedBody([
            RegisterRequest::name => '',
            RegisterRequest::handle => '',
            RegisterRequest::email => '',
            RegisterRequest::password => '',
            RegisterRequest::password_confirmation => '',
        ]));

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::TEXT_HTML, $ResponseInterface->getHeaderLine(Header::content_type));

        $body = (string) $ResponseInterface->getBody();
        $this->assertStringContainsString(self::REGISTER, haystack: $body);
        $this->assertStringContainsString('required', haystack: $body);
    }
}
