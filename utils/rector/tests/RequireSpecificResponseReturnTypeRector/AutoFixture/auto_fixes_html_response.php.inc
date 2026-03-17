<?php

namespace Utils\Rector\Tests\RequireSpecificResponseReturnTypeRector\AutoFixture;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function __invoke(): ResponseInterface
    {
        return new HtmlResponse('<h1>Hello</h1>');
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireSpecificResponseReturnTypeRector\AutoFixture;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('<h1>Hello</h1>');
    }
}

?>
