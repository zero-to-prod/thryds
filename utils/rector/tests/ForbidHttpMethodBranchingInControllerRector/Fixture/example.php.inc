<?php

namespace Utils\Rector\Tests\ForbidHttpMethodBranchingInControllerRector\Fixture;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class RegisterController
{
    public function __invoke(ServerRequestInterface $ServerRequestInterface): ResponseInterface
    {
        if ($ServerRequestInterface->getMethod() === HttpMethod::POST->value) {
            return new RedirectResponse('/login');
        }

        return new HtmlResponse('form');
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\ForbidHttpMethodBranchingInControllerRector\Fixture;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class RegisterController
{
    public function __invoke(ServerRequestInterface $ServerRequestInterface): ResponseInterface
    {
        // TODO: [ForbidHttpMethodBranchingInControllerRector] Controllers must not branch on HTTP method — declare separate #[RouteOperation] handler methods and let the router dispatch. See: utils/rector/docs/ForbidHttpMethodBranchingInControllerRector.md
        if ($ServerRequestInterface->getMethod() === HttpMethod::POST->value) {
            return new RedirectResponse('/login');
        }

        return new HtmlResponse('form');
    }
}

?>
