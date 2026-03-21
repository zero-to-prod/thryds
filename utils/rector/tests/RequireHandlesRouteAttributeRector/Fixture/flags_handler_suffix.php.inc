<?php

namespace Utils\Rector\Tests\RequireHandlesRouteAttributeRector\Fixture;

readonly class OpcacheStatusHandler
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([]);
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireHandlesRouteAttributeRector\Fixture;

// TODO: [RequireHandlesRouteAttributeRector] Attributes define properties — OpcacheStatusHandler in Controllers/ is missing #[HandlesRoute]. Every controller must declare which route it handles so the router can discover it via reflection. See: utils/rector/docs/RequireHandlesRouteAttributeRector.md
readonly class OpcacheStatusHandler
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([]);
    }
}

?>
