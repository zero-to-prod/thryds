<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use BackedEnum;
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
        public BackedEnum $BackedEnum,
        public array $params = [],
        public array $query = [],
    ) {}

    /**
     * @param array<string, string> $params
     * @param array<string, string> $query
     */
    public static function for(BackedEnum $BackedEnum, array $params = [], array $query = []): self
    {
        return new self($BackedEnum, $params, $query);
    }

    public function render(): string
    {
        $expected = RouteParam::on($this->BackedEnum);
        $provided = array_keys($this->params);

        $missing = array_diff($expected, $provided);
        if ($missing !== []) {
            throw new InvalidArgumentException(
                $this->BackedEnum->name . ' requires params: ' . implode(separator: ', ', array: $missing),
            );
        }

        $extra = array_diff($provided, $expected);
        if ($extra !== []) {
            throw new InvalidArgumentException(
                $this->BackedEnum->name . ' does not accept params: ' . implode(separator: ', ', array: $extra),
            );
        }

        $path = (string) $this->BackedEnum->value;
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
