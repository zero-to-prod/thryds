<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes;

use BackedEnum;
use InvalidArgumentException;
use Stringable;
use ZeroToProd\Framework\Attributes\AttributeCache;
use ZeroToProd\Framework\Attributes\Infrastructure;

#[Infrastructure]
readonly class RouteUrl implements Stringable
{
    use AttributeCache;

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

    /** @return string[] Extract parameter names from {placeholders} in a route path. */
    public static function paramsOf(BackedEnum $BackedEnum): array
    {
        return self::cached('paramsOf', $BackedEnum::class . '::' . $BackedEnum->name, static function () use ($BackedEnum): array {
            preg_match_all('/\{(\w+)\}/', (string) $BackedEnum->value, $matches);

            return $matches[1];
        });
    }

    public function render(): string
    {
        $expected = self::paramsOf($this->BackedEnum);
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
