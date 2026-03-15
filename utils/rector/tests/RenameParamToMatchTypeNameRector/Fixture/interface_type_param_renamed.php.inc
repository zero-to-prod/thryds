<?php

function process(ServerRequestInterface $request, ResponseInterface $response): void
{
    $response->withStatus(200);
    $request->getMethod();
}

?>
-----
<?php

function process(ServerRequestInterface $ServerRequestInterface, ResponseInterface $ResponseInterface): void
{
    $ResponseInterface->withStatus(200);
    $ServerRequestInterface->getMethod();
}

?>
