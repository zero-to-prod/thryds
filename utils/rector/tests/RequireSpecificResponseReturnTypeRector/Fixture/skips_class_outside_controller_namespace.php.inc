<?php

namespace Utils\Rector\Tests\RequireSpecificResponseReturnTypeRector\OtherNamespace;

use Psr\Http\Message\ResponseInterface;

class SomeHandler
{
    public function __invoke(): ResponseInterface
    {
        return new \Laminas\Diactoros\Response\HtmlResponse('<p>ok</p>');
    }
}
