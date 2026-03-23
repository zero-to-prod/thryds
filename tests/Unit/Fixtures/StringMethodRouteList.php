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
    addCase: 'Add case with #[Route] attribute for string method testing.',
)]
enum StringMethodRouteList: string
{
    public const string RESPONSE_KEY_CLOSURE = 'closure';

    #[Route(
        HttpMethod::GET,
        InvokableController::class,
        'Invokable controller'
    )]
    case invokable = '/_test/invokable';

    #[Route(
        HttpMethod::GET,
        [ArrayCallableController::class, 'download'],
        'Array callable'
    )]
    case array_callable = '/_test/array-callable';

    #[Route(
        HttpMethod::GET,
        static function (): JsonResponse {
            return new JsonResponse([StringMethodRouteList::RESPONSE_KEY_CLOSURE => true]);
        },
        'Closure with string method'
    )]
    case closure = '/_test/closure-string';
}

final readonly class InvokableController
{
    public const string RESPONSE_KEY = 'invokable';

    public function __invoke(): JsonResponse
    {
        return new JsonResponse([self::RESPONSE_KEY => true]);
    }
}

final readonly class ArrayCallableController
{
    public const string RESPONSE_KEY = 'download';

    public function download(): JsonResponse
    {
        return new JsonResponse([self::RESPONSE_KEY => true]);
    }
}
