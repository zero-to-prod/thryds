<?php

namespace Utils\Rector\Tests\RequireSpecificResponseReturnTypeRector\Fixture;

use Laminas\Diactoros\Response\HtmlResponse;

class PageController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('<h1>Page</h1>');
    }
}
