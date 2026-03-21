<?php

namespace Utils\Rector\Tests\ForbidHttpMethodBranchingInControllerRector\Fixture;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class AlreadyFlaggedController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: [ForbidHttpMethodBranchingInControllerRector] Controllers must not branch on HTTP method — declare separate #[RouteOperation] handler methods and let the router dispatch. See: utils/rector/docs/ForbidHttpMethodBranchingInControllerRector.md
        if ($request->getMethod() === 'POST') {
            return new RedirectResponse('/login');
        }

        return new HtmlResponse('form');
    }
}

?>
