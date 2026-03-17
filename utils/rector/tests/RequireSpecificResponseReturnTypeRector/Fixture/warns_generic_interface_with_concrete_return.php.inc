<?php

namespace Utils\Rector\Tests\RequireSpecificResponseReturnTypeRector\Fixture;

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

namespace Utils\Rector\Tests\RequireSpecificResponseReturnTypeRector\Fixture;

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    // TODO: [RequireSpecificResponseReturnTypeRector] Replace generic ResponseInterface return type with the specific response class actually returned (e.g. HtmlResponse or JsonResponse).
    public function __invoke(): ResponseInterface
    {
        return new HtmlResponse('<h1>Hello</h1>');
    }
}

?>
