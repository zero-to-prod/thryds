<?php

namespace Utils\Rector\Tests\RequireSpecificResponseReturnTypeRector\Fixture;

use Psr\Http\Message\ResponseInterface;

class ApiController
{
    // TODO: [RequireSpecificResponseReturnTypeRector] Replace generic ResponseInterface return type with the specific response class actually returned (e.g. HtmlResponse or JsonResponse).
    public function __invoke(): ResponseInterface
    {
        return new \Laminas\Diactoros\Response\JsonResponse(['ok' => true]);
    }
}
