<?php

namespace Utils\Rector\Tests\ForbidHttpMethodBranchingInControllerRector\Fixture;

use Psr\Http\Message\ServerRequestInterface;

class SomeService
{
    public function handle(ServerRequestInterface $request): void
    {
        if ($request->getMethod() === 'POST') {
            return;
        }
    }
}

?>
