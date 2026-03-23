<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use InvalidArgumentException;
use Stringable;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\RouteParam;

#[Infrastructure]
readonly class RouteUrl implements Stringable
{
    /**
     * @param array<string, string> $params
     * @param array<string, string> $query
     */
    public function __construct(
        public RouteList $RouteList,
        public array $params = [],
        public array $query = [],
    ) {}

    /**
     * @param array<string, string> $params
     * @param array<string, string> $query
     */
    public static function for(RouteList $RouteList, array $params = [], array $query = []): self
    {
        return new self($RouteList, $params, $query);
    }

    public function render(): string
    {
        $expected = RouteParam::on($this->RouteList);
        $provided = array_keys($this->params);

        $missing = array_diff($expected, $provided);
        if ($missing !== []) {
            throw new InvalidArgumentException(
                $this->RouteList->name . ' requires params: ' . implode(separator: ', ', array: $missing),
            );
        }

        $extra = array_diff($provided, $expected);
        if ($extra !== []) {
            throw new InvalidArgumentException(
                $this->RouteList->name . ' does not accept params: ' . implode(separator: ', ', array: $extra),
            );
        }

        $path = $this->RouteList->value;
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
