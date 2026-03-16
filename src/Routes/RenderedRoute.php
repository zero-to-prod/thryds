<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use Stringable;

readonly class RenderedRoute implements Stringable
{
    public function __construct(
        public Route $Route,
        public array $params = [],
        public array $query = [],
    ) {}

    public function render(): string
    {
        $path = $this->Route->value;
        foreach ($this->params as $key => $value) {
            $path = str_replace(search: "{{$key}}", replace: $value, subject: $path);
        }

        return $this->query
            ? $path . '?' . http_build_query(data: $this->query)
            : $path;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
