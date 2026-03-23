<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit\Fixtures;

use Laminas\Diactoros\Response\JsonResponse;
use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Routes\HttpMethod;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::url_routes,
    addCase: 'Add case with #[Route] attribute for closure action testing.',
)]
enum ClosureRouteList: string
{
    public const string RESPONSE_KEY_OK = 'ok';

    #[Route(
        HttpMethod::GET,
        static function (): JsonResponse {
            return new JsonResponse([ClosureRouteList::RESPONSE_KEY_OK => true]);
        },
        'Closure action test route',
    )]
    case ping = '/_test/ping';
}
