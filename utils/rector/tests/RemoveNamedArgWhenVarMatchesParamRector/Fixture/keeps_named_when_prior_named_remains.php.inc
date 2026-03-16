<?php

namespace Utils\Rector\Tests\RemoveNamedArgWhenVarMatchesParamRector\Fixture;

class RenderedRoute
{
    public function __construct(
        public string $Route,
        public array $params = [],
        public array $query = [],
    ) {}
}

new RenderedRoute(Route: 'home', params: $params, query: $query);

?>
-----
<?php

namespace Utils\Rector\Tests\RemoveNamedArgWhenVarMatchesParamRector\Fixture;

class RenderedRoute
{
    public function __construct(
        public string $Route,
        public array $params = [],
        public array $query = [],
    ) {}
}

new RenderedRoute(Route: 'home', params: $params, query: $query);

?>
